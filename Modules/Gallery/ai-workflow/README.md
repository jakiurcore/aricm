# Gallery Module

## Purpose
Albums and photos: multi-upload (shrunk to 1600 px with a 400 px square thumbnail when GD/Imagick exist), drag-to-sort, cover photo, public gallery with an Alpine lightbox, homepage preview. **Depends on Core only.**

## Public surface
- `Services\GalleryService`: `addPhotos`, `reorder` (ignores ids of other albums), `deletePhoto` (clears the cover, removes files), `deleteAlbum`.
- Implements `ProvidesHomepageSections` (`gallery::home.preview`, order 70) and `ProvidesPermissions` (`gallery.view|manage`).
- Public routes `gallery.public.index|show` (published albums with photos only); admin API `gallery.api.*`.

## Rules
- Image handling goes through Core's `ImageUploader`, so uploads work (unprocessed) on hosts without GD.
- Albums that are unpublished or empty never appear publicly.
