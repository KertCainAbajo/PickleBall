<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Photo upload / replace / remove for admin forms (courts, paddles).
 * The model must use App\Models\Concerns\HasPhoto and define UPLOAD_DIR.
 */
trait ManagesPhoto
{
    use WithFileUploads;

    /** New photo chosen in the form (not saved until "Save"). */
    public ?TemporaryUploadedFile $photo = null;

    /** URL of the record's current photo while editing. */
    public ?string $currentPhotoUrl = null;

    /** Remove the current photo on save. */
    public bool $removePhoto = false;

    protected function photoRules(): array
    {
        return ['photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096']]; // 4 MB
    }

    protected function photoMessages(): array
    {
        return [
            'photo.image' => 'The photo must be an image (JPG, PNG or WebP).',
            'photo.mimes' => 'The photo must be a JPG, PNG or WebP file.',
            'photo.max' => 'The photo must be 4 MB or smaller.',
        ];
    }

    public function updatedPhoto(): void
    {
        $this->validateOnly('photo');
        $this->removePhoto = false;
    }

    /** Store a new photo / remove the old one, and return the image_path change to save (if any). */
    protected function applyPhoto(Model $record): array
    {
        if ($this->photo) {
            $record->deleteUploadedImage();

            return ['image_path' => 'uploads/'.$this->photo->store($record::UPLOAD_DIR, 'uploads')];
        }

        if ($this->removePhoto) {
            $record->deleteUploadedImage();

            return ['image_path' => null];
        }

        return [];
    }

    protected function resetPhoto(): void
    {
        $this->reset('photo', 'currentPhotoUrl', 'removePhoto');
    }
}
