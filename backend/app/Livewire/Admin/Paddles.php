<?php

namespace App\Livewire\Admin;

use App\Models\Paddle;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Manage Paddles')]
class Paddles extends Component
{
    use WithFileUploads;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $brand = '';

    public string $description = '';

    public string $hourly_rate = '';

    public string $stock_quantity = '';

    public bool $is_active = true;

    /** New photo chosen in the form (not saved until "Save"). */
    public ?TemporaryUploadedFile $photo = null;

    /** URL of the paddle's current photo while editing. */
    public ?string $currentPhotoUrl = null;

    /** Remove the current photo on save. */
    public bool $removePhoto = false;

    public ?string $notice = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'hourly_rate' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:10000'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'], // 4 MB
        ];
    }

    protected function messages(): array
    {
        return [
            'photo.image' => 'The photo must be an image (JPG, PNG or WebP).',
            'photo.max' => 'The photo must be 4 MB or smaller.',
        ];
    }

    public function updatedPhoto(): void
    {
        $this->validateOnly('photo');
        $this->removePhoto = false;
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $paddle = Paddle::findOrFail($id);

        $this->resetForm();
        $this->editingId = $paddle->id;
        $this->name = $paddle->name;
        $this->brand = (string) $paddle->brand;
        $this->description = (string) $paddle->description;
        $this->hourly_rate = (string) $paddle->hourly_rate;
        $this->stock_quantity = (string) $paddle->stock_quantity;
        $this->is_active = $paddle->is_active;
        $this->currentPhotoUrl = $paddle->image_url;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();
        unset($data['photo']);
        $data['brand'] = $data['brand'] ?: null;
        $data['description'] = $data['description'] ?: null;

        $paddle = $this->editingId ? Paddle::findOrFail($this->editingId) : new Paddle;

        if ($this->photo) {
            $paddle->deleteUploadedImage();
            $data['image_path'] = 'uploads/'.$this->photo->store(Paddle::UPLOAD_DIR, 'uploads');
        } elseif ($this->removePhoto) {
            $paddle->deleteUploadedImage();
            $data['image_path'] = null;
        }

        $paddle->fill($data)->save();
        $this->notice = $this->editingId ? "{$paddle->name} updated." : "{$paddle->name} added.";

        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $paddle = Paddle::findOrFail($id);
        $paddle->update(['is_active' => ! $paddle->is_active]);

        $this->notice = "{$paddle->name} is now ".($paddle->is_active ? 'available for rent' : 'hidden from customers').'.';
    }

    public function delete(int $id): void
    {
        $paddle = Paddle::findOrFail($id);

        if ($paddle->rentals()->exists()) {
            $this->notice = "{$paddle->name} has rental history, so it can't be deleted. Deactivate it instead.";

            return;
        }

        $paddle->deleteUploadedImage();
        $paddle->delete();
        $this->notice = "{$paddle->name} deleted.";
    }

    public function resetForm(): void
    {
        $this->reset('showForm', 'editingId', 'name', 'brand', 'description', 'hourly_rate', 'stock_quantity', 'is_active', 'photo', 'currentPhotoUrl', 'removePhoto');
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.paddles', [
            'paddles' => Paddle::withCount('rentals')->orderBy('name')->get(),
        ]);
    }
}
