<?php

namespace Modules\PersonalAccounting\Repositories;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\PersonalAccounting\Interfaces\PersonalTransactionRepositoryInterface;
use Modules\PersonalAccounting\Models\PersonalTransaction;

class PersonalTransactionRepository extends BaseRepository implements PersonalTransactionRepositoryInterface
{
    protected string $model = PersonalTransaction::class;

    public function findByDateRange(int $userId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->tenantScope()
            ->with(['account:id,name', 'category:id,name'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('date')
            ->get();
    }

    public function sumByCategory(int $userId, string $type, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->tenantScope()
            ->selectRaw('category_id, COALESCE(SUM(amount), 0) as total')
            ->where('user_id', $userId)
            ->where('type', $type)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->with('category:id,name')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category?->name ?? 'Uncategorised',
                'total' => (float) $row->total,
            ]);
    }

    public function sumByType(int $userId, string $type, CarbonInterface $from, CarbonInterface $to): float
    {
        return (float) $this->tenantScope()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');
    }

    public function monthlyTrend(int $userId, string $type, int $months = 12): Collection
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $rows = $this->tenantScope()
            ->select(['date', 'amount'])
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('date', '>=', $start->toDateString())
            ->get();

        $byMonth = $rows->groupBy(fn ($row) => $row->date->format('Y-m'))->map->sum('amount');

        // Back-fill every month so charts always have a full series.
        return collect(range($months - 1, 0))->map(function (int $offset) use ($byMonth): array {
            $month = now()->startOfMonth()->subMonths($offset);

            return [
                'month' => $month->format('Y-m'),
                'label' => $month->format('M Y'),
                'total' => (float) ($byMonth[$month->format('Y-m')] ?? 0),
            ];
        })->values();
    }
}
