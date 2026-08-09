<?php

namespace Modules\CORE\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return Activity::query()
            ->with(['causer:id,name,email'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->string('search'));
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhereHas('causer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->when($request->filled('event'), fn ($query) => $query->where('event', $request->string('event')))
            ->when($request->filled('sort') && $request->filled('direction'), function ($query) use ($request): void {
                $sort = $request->string('sort');
                $direction = $request->string('direction') === 'asc' ? 'asc' : 'desc';
                $column = in_array($sort, ['description', 'event', 'created_at'], true) ? (string) $sort : 'created_at';
                $query->orderBy($column, $direction);
            }, fn ($query) => $query->latest())
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();
    }
}
