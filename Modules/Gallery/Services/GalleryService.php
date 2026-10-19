<?php

namespace Modules\Gallery\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\ImageUploader;
use Modules\Gallery\Models\Album;
use Modules\Gallery\Models\Photo;

/**
 * Adds, orders and removes photos. Uploads are shrunk to 1600 px with a square thumbnail (when GD/Imagick exist).
 */
class GalleryService
{
    public function __construct(protected ImageUploader $images) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return Collection<int, Photo>
     */
    public function addPhotos(Album $album, array $files): Collection
    {
        return DB::transaction(function () use ($album, $files): Collection {
            $position = (int) $album->photos()->max('sort_order');

            return collect($files)->map(function (UploadedFile $file) use ($album, &$position): Photo {
                $image = $this->images->store($file, "gallery/{$album->id}", 1600);

                return $album->photos()->create(['image' => $image, 'thumbnail' => $this->images->thumbnail($image, 400), 'sort_order' => ++$position]);
            });
        });
    }

    /**
     * Persist a drag-and-drop order. Ids that do not belong to the album are ignored, never reassigned.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(Album $album, array $orderedIds): void
    {
        DB::transaction(function () use ($album, $orderedIds): void {
            foreach (array_values($orderedIds) as $index => $id) {
                Photo::query()->where(['id' => $id, 'album_id' => $album->id])->update(['sort_order' => $index + 1]);
            }
        });
    }

    public function deletePhoto(Photo $photo): void
    {
        DB::transaction(function () use ($photo): void {
            Album::query()->where('cover_photo_id', $photo->id)->update(['cover_photo_id' => null]);
            $this->images->delete($photo->image, $photo->thumbnail === $photo->image ? null : $photo->thumbnail);
            $photo->delete();
        });
    }

    public function deleteAlbum(Album $album): void
    {
        foreach ($album->photos as $photo) {
            $this->images->delete($photo->image, $photo->thumbnail === $photo->image ? null : $photo->thumbnail);
        }

        $album->delete();
    }
}
