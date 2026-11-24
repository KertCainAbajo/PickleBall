<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

/**
 * A model with an optional photo in the `image_path` column (relative to public/).
 *
 * Admin uploads live on the "uploads" disk (public/uploads/<UPLOAD_DIR>/...); bundled
 * defaults live in public/images/... and are never deleted. The using model defines
 * `public const UPLOAD_DIR = '...'`.
 *
 * @property string|null $image_path
 * @property-read string|null $image_url
 */
trait HasPhoto
{
    /** Public URL of the photo, or null when there is none. */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image_path ? asset($this->image_path) : null);
    }

    /** Delete the photo file if an admin uploaded it (bundled default images are kept). */
    public function deleteUploadedImage(): void
    {
        if ($this->image_path && str_starts_with($this->image_path, 'uploads/')) {
            Storage::disk('uploads')->delete(substr($this->image_path, strlen('uploads/')));
        }
    }
}
