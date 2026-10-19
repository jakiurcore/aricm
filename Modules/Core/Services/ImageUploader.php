<?php

namespace Modules\Core\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

/**
 * Stores uploaded images on the public disk, shrinking them to a maximum side when GD or Imagick is available.
 * Without either extension the original file is stored untouched, so uploads never fail on a bare host.
 */
class ImageUploader
{
    public function canProcess(): bool
    {
        return extension_loaded('gd') || extension_loaded('imagick');
    }

    /**
     * @return string Path on the public disk.
     */
    public function store(UploadedFile $file, string $directory, int $maxSide = 1600): string
    {
        if (! $this->canProcess() || ! str_starts_with((string) $file->getMimeType(), 'image/')) {
            return $file->store($directory, 'public');
        }

        $extension = in_array($file->extension(), ['png', 'webp'], true) ? $file->extension() : 'jpg';
        $path = trim($directory, '/').'/'.Str::uuid().'.'.$extension;

        $image = $this->manager()->decodePath($file->getRealPath())->scaleDown(width: $maxSide, height: $maxSide);
        Storage::disk('public')->put($path, (string) $image->encodeUsingFileExtension($extension, quality: 82));

        return $path;
    }

    /**
     * Store a square-cropped thumbnail of an already stored image; returns its path, or the original path when
     * images cannot be processed.
     */
    public function thumbnail(string $path, int $side = 400): string
    {
        if (! $this->canProcess()) {
            return $path;
        }

        $thumbnail = 'thumbs/'.$path;
        $image = $this->manager()->decodePath(Storage::disk('public')->path($path))->cover($side, $side);
        Storage::disk('public')->put($thumbnail, (string) $image->encodeUsingFileExtension(pathinfo($path, PATHINFO_EXTENSION), quality: 80));

        return $thumbnail;
    }

    public function delete(?string ...$paths): void
    {
        Storage::disk('public')->delete(array_filter($paths));
    }

    protected function manager(): ImageManager
    {
        return new ImageManager(extension_loaded('gd') ? new GdDriver : new ImagickDriver);
    }
}
