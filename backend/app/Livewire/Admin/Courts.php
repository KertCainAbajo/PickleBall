<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ManagesPhoto;
use App\Models\Court;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manage Courts')]
class Courts extends Component
{
    use ManagesPhoto;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $surface_type = '';

    public string $hourly_rate = '';

    public string $opening_time = '06:00';

    public string $closing_time = '22:00';

    public bool $is_active = true;

    public ?string $notice = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'surface_type' => ['nullable', 'string', 'max:100'],
            'hourly_rate' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i', 'after:opening_time'],
            'is_active' => ['boolean'],
            ...$this->photoRules(),
        ];
    }

    protected function messages(): array
    {
        return $this->photoMessages();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $court = Court::findOrFail($id);

        $this->resetForm();
        $this->editingId = $court->id;
        $this->name = $court->name;
        $this->description = (string) $court->description;
        $this->surface_type = (string) $court->surface_type;
        $this->hourly_rate = (string) $court->hourly_rate;
        $this->opening_time = Carbon::parse($court->opening_time)->format('H:i');
        $this->closing_time = Carbon::parse($court->closing_time)->format('H:i');
        $this->is_active = $court->is_active;
        $this->currentPhotoUrl = $court->image_url;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();
        unset($data['photo']);
        $data['description'] = $data['description'] ?: null;
        $data['surface_type'] = $data['surface_type'] ?: null;

        $court = $this->editingId ? Court::findOrFail($this->editingId) : new Court;
        $court->fill($data + $this->applyPhoto($court))->save();

        $this->notice = $this->editingId ? "{$court->name} updated." : "{$court->name} added.";
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $court = Court::findOrFail($id);
        $court->update(['is_active' => ! $court->is_active]);

        $this->notice = "{$court->name} is now ".($court->is_active ? 'open for booking' : 'hidden from customers').'.';
    }

    public function delete(int $id): void
    {
        $court = Court::findOrFail($id);

        if ($court->bookings()->exists()) {
            $this->notice = "{$court->name} has bookings, so it can't be deleted. Deactivate it instead.";

            return;
        }

        $court->deleteUploadedImage();
        $court->delete();
        $this->notice = "{$court->name} deleted.";
    }

    public function resetForm(): void
    {
        $this->reset('showForm', 'editingId', 'name', 'description', 'surface_type', 'hourly_rate', 'opening_time', 'closing_time', 'is_active');
        $this->resetPhoto();
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.courts', [
            'courts' => Court::withCount('bookings')->orderBy('name')->get(),
        ]);
    }
}
