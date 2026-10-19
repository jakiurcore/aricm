<?php

namespace Modules\Gallery\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Gallery\Http\Requests\AlbumRequest;
use Modules\Gallery\Http\Requests\UploadPhotosRequest;
use Modules\Gallery\Models\Album;
use Modules\Gallery\Models\Photo;
use Modules\Gallery\Services\GalleryService;

class AlbumController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('gallery.view'), 403);

        $albums = Album::query()->withCount('photos')->orderBy('sort_order')->orderByDesc('album_date')->orderByDesc('id')->get();

        return response()->json(['data' => $albums->map(fn (Album $album): array => $this->present($album))->values()]);
    }

    public function show(Request $request, Album $album): JsonResponse
    {
        abort_unless($request->user()->can('gallery.view'), 403);

        return response()->json(['data' => [...$this->present($album->loadCount('photos')), 'photos' => $album->photos->map(fn (Photo $photo): array => $this->photo($photo))->values()]]);
    }

    public function store(AlbumRequest $request): JsonResponse
    {
        $album = Album::create([...$request->validated(), 'sort_order' => $request->integer('sort_order')]);

        return response()->json(['data' => $this->present($album->loadCount('photos'))], 201);
    }

    public function update(AlbumRequest $request, Album $album): JsonResponse
    {
        $album->update([...$request->validated(), 'sort_order' => $request->integer('sort_order')]);

        return response()->json(['data' => $this->present($album->loadCount('photos'))]);
    }

    public function destroy(Request $request, Album $album, GalleryService $gallery): JsonResponse
    {
        abort_unless($request->user()->can('gallery.manage'), 403);

        $gallery->deleteAlbum($album);

        return response()->json(null, 204);
    }

    public function upload(UploadPhotosRequest $request, Album $album, GalleryService $gallery): JsonResponse
    {
        $photos = $gallery->addPhotos($album, $request->file('photos'));

        return response()->json(['data' => $photos->map(fn (Photo $photo): array => $this->photo($photo))->values()], 201);
    }

    public function reorder(Request $request, Album $album, GalleryService $gallery): JsonResponse
    {
        abort_unless($request->user()->can('gallery.manage'), 403);

        $gallery->reorder($album, $request->validate(['ids' => ['required', 'array', 'max:500'], 'ids.*' => ['integer']])['ids']);

        return response()->json(['data' => ['ok' => true]]);
    }

    public function updatePhoto(Request $request, Photo $photo): JsonResponse
    {
        abort_unless($request->user()->can('gallery.manage'), 403);

        $photo->update($request->validate(['caption_bn' => ['nullable', 'string', 'max:255'], 'caption_en' => ['nullable', 'string', 'max:255']]));

        return response()->json(['data' => $this->photo($photo)]);
    }

    public function cover(Request $request, Album $album, Photo $photo): JsonResponse
    {
        abort_unless($request->user()->can('gallery.manage'), 403);
        abort_unless($photo->album_id === $album->id, 404);

        $album->update(['cover_photo_id' => $photo->id]);

        return response()->json(['data' => $this->present($album->loadCount('photos'))]);
    }

    public function destroyPhoto(Request $request, Photo $photo, GalleryService $gallery): JsonResponse
    {
        abort_unless($request->user()->can('gallery.manage'), 403);

        $gallery->deletePhoto($photo);

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(Album $album): array
    {
        return [
            'id' => $album->id, 'title_bn' => $album->title_bn, 'title_en' => $album->title_en,
            'description_bn' => $album->description_bn, 'description_en' => $album->description_en,
            'album_date' => $album->album_date?->toDateString(), 'is_published' => $album->is_published, 'sort_order' => $album->sort_order,
            'photos_count' => $album->photos_count ?? null, 'cover_photo_id' => $album->cover_photo_id, 'cover_url' => $album->coverUrl(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function photo(Photo $photo): array
    {
        return ['id' => $photo->id, 'url' => $photo->url(), 'thumbnail_url' => $photo->thumbnailUrl(), 'caption_bn' => $photo->caption_bn, 'caption_en' => $photo->caption_en, 'sort_order' => $photo->sort_order];
    }
}
