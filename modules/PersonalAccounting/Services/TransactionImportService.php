<?php

namespace Modules\PersonalAccounting\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\PersonalAccounting\Actions\CreateTransactionAction;
use Modules\PersonalAccounting\Models\PersonalAccount;
use Modules\PersonalAccounting\Models\PersonalTransaction;

/**
 * Parses, maps and imports CSV bank statements into the ledger.
 *
 * Column detection is heuristic: the service looks at header names and row
 * values to figure out which column holds the date, amount, description, type
 * and category. Import runs inside a transaction and reports a result summary.
 */
class TransactionImportService
{
    public function __construct(private readonly CreateTransactionAction $createTransaction)
    {
    }

    /**
     * Parse an uploaded CSV into an array of associative rows (normalised keys).
     *
     * @return array<int, array<string, string>>
     */
    public function parseCSV(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            abort(422, 'Could not open the uploaded file.');
        }

        $rows = [];
        $header = null;
        $rowIndex = 0;

        while (($line = fgetcsv($handle)) !== false) {
            $line = array_map(fn ($cell) => trim((string) $cell), $line);

            // Skip empty rows.
            if (count(array_filter($line)) === 0) {
                continue;
            }

            if ($header === null) {
                $header = $this->normaliseHeader($line);
                continue;
            }

            // Pad/truncate so each row matches the header length.
            $count = count($header);
            $line = array_slice(array_pad($line, $count, ''), 0, $count);

            $rows[] = array_combine($header, $line);
            $rowIndex++;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Auto-detect which column index maps to which logical field.
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array<string, string>  e.g. ['date' => 'Date', 'amount' => 'Amount', ...]
     */
    public function detectColumns(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $headers = array_keys($rows[0]);
        $normalised = array_map('strtolower', $headers);

        $map = [
            'date' => null,
            'amount' => null,
            'description' => null,
            'type' => null,
            'category' => null,
        ];

        foreach ($normalised as $i => $header) {
            $header = $header;

            if ($map['date'] === null && $this->matchesDateColumn($header, $rows, $i)) {
                $map['date'] = $headers[$i];
            } elseif ($map['amount'] === null && $this->matchesAmountColumn($header)) {
                $map['amount'] = $headers[$i];
            } elseif ($map['description'] === null && $this->matchesDescriptionColumn($header)) {
                $map['description'] = $headers[$i];
            } elseif ($map['type'] === null && $this->matchesTypeColumn($header)) {
                $map['type'] = $headers[$i];
            } elseif ($map['category'] === null && $this->matchesCategoryColumn($header)) {
                $map['category'] = $headers[$i];
            }
        }

        return array_filter($map);
    }

    /**
     * Return the first few mapped rows for user confirmation.
     *
     * @param  array<int, array<string, string>>  $rows
     * @param  array<string, string>  $columnMap
     * @return array<int, array<string, string>>
     */
    public function preview(array $rows, array $columnMap, int $limit = 5): array
    {
        return collect(array_slice($rows, 0, $limit))
            ->map(function (array $row) use ($columnMap): array {
                $mapped = [];
                foreach ($columnMap as $field => $column) {
                    $mapped[$field] = $row[$column] ?? '';
                }

                return $mapped;
            })
            ->values()
            ->all();
    }

    /**
     * Import mapped rows, creating PersonalTransaction records.
     *
     * @param  array<int, array<string, string>>  $rows
     * @param  array<string, string>  $columnMap
     * @return array<string, int>  ['imported' => x, 'skipped' => y, 'failed' => z]
     */
    public function import(array $rows, array $columnMap, int $accountId, int $userId, int $tenantId): array
    {
        $account = PersonalAccount::query()->forTenant($tenantId)->findOrFail($accountId);

        $result = ['imported' => 0, 'skipped' => 0, 'failed' => 0];

        DB::transaction(function () use ($rows, $columnMap, $account, $userId, $tenantId, &$result): void {
            foreach ($rows as $row) {
                $mapped = $this->mapRow($row, $columnMap);

                // Skip rows missing required fields or with a zero amount.
                if ($mapped['date'] === null || $mapped['amount'] === null || $mapped['amount'] == 0) {
                    $result['skipped']++;

                    continue;
                }

                try {
                    $this->createTransaction->handle([
                        'tenant_id' => $tenantId,
                        'user_id' => $userId,
                        'account_id' => $account->id,
                        'type' => $mapped['type'],
                        'amount' => $mapped['amount'],
                        'date' => $mapped['date'],
                        'note' => $mapped['description'],
                        'category_id' => $mapped['category_id'],
                    ]);

                    $result['imported']++;
                } catch (\Throwable) {
                    $result['failed']++;
                }
            }
        });

        return $result;
    }

    /**
     * Persist an import log entry.
     *
     * @param  array<string, mixed>  $data
     * @return \Modules\PersonalAccounting\Models\PersonalImportLog
     */
    public function log(array $data)
    {
        return \Modules\PersonalAccounting\Models\PersonalImportLog::create($data);
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, string>  $columnMap
     * @return array{date: string|null, amount: float|null, type: string, description: string|null, category_id: int|null}
     */
    private function mapRow(array $row, array $columnMap): array
    {
        $dateRaw = $columnMap['date'] ?? null ? ($row[$columnMap['date']] ?? '') : '';
        $amountRaw = $columnMap['amount'] ?? null ? ($row[$columnMap['amount']] ?? '') : '';
        $typeRaw = $columnMap['type'] ?? null ? strtolower($row[$columnMap['type']] ?? '') : '';
        $desc = $columnMap['description'] ?? null ? ($row[$columnMap['description']] ?? '') : null;

        $signedAmount = $this->parseAmount($amountRaw);
        $date = $this->parseDate($dateRaw);
        $type = $this->resolveType($typeRaw, $signedAmount);
        // Store a positive amount; the sign is captured by the resolved type.
        $amount = $signedAmount === null ? null : abs($signedAmount);

        $categoryName = $columnMap['category'] ?? null ? ($row[$columnMap['category']] ?? '') : '';
        $categoryId = null;

        if ($categoryName !== '') {
            $categoryId = \Modules\PersonalAccounting\Models\PersonalCategory::query()
                ->where('tenant_id', auth()->user()?->tenant_id)
                ->where('name', $categoryName)
                ->where('type', $type)
                ->value('id');
        }

        return [
            'date' => $date,
            'amount' => $amount,
            'type' => $type,
            'description' => $desc,
            'category_id' => $categoryId,
        ];
    }

    /**
     * @param  array<int, string>  $line
     * @return array<int, string>
     */
    private function normaliseHeader(array $line): array
    {
        return array_map(function (string $cell): string {
            $cell = strtolower($cell);
            // Normalise common variations.
            $cell = str_replace(['-', ' ', '.', '(', ')'], '_', $cell);
            $cell = preg_replace('/_+/', '_', $cell) ?? $cell;

            return trim($cell, '_');
        }, $line);
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function matchesDateColumn(string $header, array $rows, int $index): bool
    {
        if (str_contains($header, 'date') || str_contains($header, 'time') || $header === 'posting_date') {
            return true;
        }

        // Heuristic: look at a sample value that looks like a date.
        foreach (array_slice($rows, 0, 5) as $row) {
            $keys = array_keys($row);
            if (isset($keys[$index])) {
                $value = $row[$keys[$index]] ?? '';
                if (strtotime($value) !== false && preg_match('/\d{1,4}[-\/]\d{1,2}[-\/]\d{1,4}/', $value)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function matchesAmountColumn(string $header): bool
    {
        return str_contains($header, 'amount')
            || str_contains($header, 'amt')
            || str_contains($header, 'value')
            || str_contains($header, 'debit')
            || str_contains($header, 'credit')
            || str_contains($header, 'balance');
    }

    private function matchesDescriptionColumn(string $header): bool
    {
        return str_contains($header, 'desc')
            || str_contains($header, 'note')
            || str_contains($header, 'detail')
            || str_contains($header, 'narration')
            || str_contains($header, 'memo')
            || str_contains($header, 'payee');
    }

    private function matchesTypeColumn(string $header): bool
    {
        return str_contains($header, 'type')
            || str_contains($header, 'debit_credit')
            || str_contains($header, 'dr_cr');
    }

    private function matchesCategoryColumn(string $header): bool
    {
        return str_contains($header, 'categor')
            || str_contains($header, 'class');
    }

    private function parseAmount(string $raw): ?float
    {
        // Remove currency symbols, commas, spaces. Preserve negative sign.
        $cleaned = preg_replace('/[^\d.\-]/', '', $raw) ?? '';
        $value = (float) $cleaned;

        return $value === 0.0 && trim($raw) === '' ? null : $value;
    }

    private function parseDate(string $raw): ?string
    {
        $timestamp = strtotime($raw);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    private function resolveType(string $typeRaw, ?float $amount): string
    {
        if ($typeRaw === 'income' || $typeRaw === 'credit' || $typeRaw === 'in') {
            return 'income';
        }

        if ($typeRaw === 'expense' || $typeRaw === 'debit' || $typeRaw === 'out') {
            return 'expense';
        }

        // If the amount is negative it's an expense, positive it's income.
        if ($amount !== null) {
            return $amount < 0 ? 'expense' : 'income';
        }

        return 'expense';
    }
}
