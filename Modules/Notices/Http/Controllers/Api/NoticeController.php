<?php

namespace Modules\Notices\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Modules\Notices\Http\Requests\NoticeRequest;
use Modules\Notices\Http\Resources\NoticeResource;
use Modules\Notices\Models\Notice;
use Modules\Notices\Support\SafeHtml;

class NoticeController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->can('notices.view'), 403);

        $notices = Notice::query()
            ->when($request->string('category')->toString(), fn ($query, string $category) => $query->where('category', $category))
            ->when($request->string('search')->toString(), fn ($query, string $term) => $query->where(fn ($inner) => $inner->where('title_bn', 'like', "%{$term}%")->orWhere('title_en', 'like', "%{$term}%")))
            ->orderByDesc('is_pinned')->orderByRaw('coalesce(publish_at, created_at) desc')
            ->paginate(20);

        return NoticeResource::collection($notices);
    }

    public function store(NoticeRequest $request): JsonResponse
    {
        $notice = Notice::create($this->attributes($request));

        return (new NoticeResource($notice))->response()->setStatusCode(201);
    }

    public function update(NoticeRequest $request, Notice $notice): NoticeResource
    {
        $data = $this->attributes($request);

        if ($request->hasFile('attachment') || $request->boolean('remove_attachment')) {
            $this->deleteAttachment($notice);
        }

        if (! $request->hasFile('attachment') && ! $request->boolean('remove_attachment')) {
            unset($data['attachment'], $data['attachment_name']);
        }

        $notice->update($data);

        return new NoticeResource($notice);
    }

    public function destroy(Request $request, Notice $notice): JsonResponse
    {
        abort_unless($request->user()->can('notices.manage'), 403);

        $this->deleteAttachment($notice);
        $notice->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes(NoticeRequest $request): array
    {
        $data = $request->safe()->except(['attachment', 'remove_attachment']);
        $data['body_bn'] = SafeHtml::clean($data['body_bn']);
        $data['body_en'] = isset($data['body_en']) ? SafeHtml::clean($data['body_en']) : null;

        if ($file = $request->file('attachment')) {
            $data['attachment'] = $file->store('notices', 'public');
            $data['attachment_name'] = $file->getClientOriginalName();
        } else {
            $data['attachment'] = null;
            $data['attachment_name'] = null;
        }

        return $data;
    }

    protected function deleteAttachment(Notice $notice): void
    {
        if ($notice->attachment) {
            Storage::disk('public')->delete($notice->attachment);
        }
    }
}
