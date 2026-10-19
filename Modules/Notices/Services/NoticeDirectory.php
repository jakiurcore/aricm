<?php

namespace Modules\Notices\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Notices\Models\Notice;

/**
 * Read API for pages and portals: only ever returns notices the public may see.
 */
class NoticeDirectory
{
    /**
     * @return Collection<int, Notice>
     */
    public function latest(int $limit = 5): Collection
    {
        return Notice::query()->visible()->orderByDesc('is_pinned')->orderByRaw('coalesce(publish_at, created_at) desc')->limit($limit)->get();
    }

    /**
     * @return Collection<int, Notice>
     */
    public function ticker(int $limit = 8): Collection
    {
        return Notice::query()->visible()->where('show_in_ticker', true)->orderByRaw('coalesce(publish_at, created_at) desc')->limit($limit)->get();
    }

    /**
     * @return LengthAwarePaginator<Notice>
     */
    public function paginate(?string $category = null, ?string $search = null, int $perPage = 12): LengthAwarePaginator
    {
        return Notice::query()->visible()
            ->when($category, fn ($query, string $value) => $query->where('category', $value))
            ->when($search, fn ($query, string $term) => $query->where(fn ($inner) => $inner->where('title_bn', 'like', "%{$term}%")->orWhere('title_en', 'like', "%{$term}%")))
            ->orderByDesc('is_pinned')->orderByRaw('coalesce(publish_at, created_at) desc')
            ->paginate($perPage)->withQueryString();
    }

    public function find(int $id): ?Notice
    {
        return Notice::query()->visible()->find($id);
    }
}
