<?php

namespace App\Livewire\Admin;

use App\Models\Paddle;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manage Paddles')]
class Paddles extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $brand = '';

    public string $description = '';

    public string $hourly_rate = '';

    public string $stock_quantity = '';

    public bool $is_active = true;

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
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $paddle = Paddle::findOrFail($id);

        $this->resetValidation();
        $this->editingId = $paddle->id;
        $this->name = $paddle->name;
        $this->brand = (string) $paddle->brand;
        $this->description = (string) $paddle->description;
        $this->hourly_rate = (string) $paddle->hourly_rate;
        $this->stock_quantity = (string) $paddle->stock_quantity;
        $this->is_active = $paddle->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['brand'] = $data['brand'] ?: null;
        $data['description'] = $data['description'] ?: null;

        if ($this->editingId) {
            Paddle::findOrFail($this->editingId)->update($data);
            $this->notice = "{$data['name']} updated.";
        } else {
            Paddle::create($data);
            $this->notice = "{$data['name']} added.";
        }

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

        $paddle->delete();
        $this->notice = "{$paddle->name} deleted.";
    }

    public function resetForm(): void
    {
        $this->reset('showForm', 'editingId', 'name', 'brand', 'description', 'hourly_rate', 'stock_quantity', 'is_active');
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.paddles', [
            'paddles' => Paddle::withCount('rentals')->orderBy('name')->get(),
        ]);
    }
}
