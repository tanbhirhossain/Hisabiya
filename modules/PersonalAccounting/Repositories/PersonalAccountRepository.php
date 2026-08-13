<?php

namespace Modules\PersonalAccounting\Repositories;

use Illuminate\Support\Collection;
use Modules\PersonalAccounting\Interfaces\PersonalAccountRepositoryInterface;
use Modules\PersonalAccounting\Models\PersonalAccount;

class PersonalAccountRepository extends BaseRepository implements PersonalAccountRepositoryInterface
{
    protected string $model = PersonalAccount::class;

    public function balanceSummary(int $userId): array
    {
        $accounts = $this->tenantScope()
            ->where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return [
            'total_balance' => (float) $accounts->sum('balance'),
            'count' => $accounts->count(),
            'by_type' => $accounts->groupBy('type')->map(function (Collection $group): array {
                return [
                    'count' => $group->count(),
                    'balance' => (float) $group->sum('balance'),
                ];
            }),
            'accounts' => $accounts->map(fn (PersonalAccount $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'currency' => $account->currency,
                'balance' => (float) $account->balance,
                'is_default' => $account->is_default,
                'icon' => $account->icon,
                'color' => $account->color,
            ]),
        ];
    }

    public function recentTransactions(int $userId, int $limit = 10): Collection
    {
        return \Modules\PersonalAccounting\Models\PersonalTransaction::query()
            ->withoutGlobalScope(\Modules\PersonalAccounting\Traits\Scopes\TenantScope::class)
            ->where('tenant_id', auth()->user()?->tenant_id)
            ->where('user_id', $userId)
            ->with(['account:id,name', 'toAccount:id,name', 'category:id,name'])
            ->latest('date')
            ->limit($limit)
            ->get();
    }
}
