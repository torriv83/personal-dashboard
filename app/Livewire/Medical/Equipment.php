<?php

namespace App\Livewire\Medical;

use App\Models\Category;
use App\Models\Equipment as EquipmentModel;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Equipment extends Component
{
    public string $search = '';

    public ?string $selectedCategory = null;

    public bool $showEquipmentModal = false;

    public bool $showCategoryModal = false;

    public ?int $editingEquipmentId = null;

    public ?int $editingCategoryId = null;

    // Equipment form fields
    public string $equipmentType = '';

    public string $equipmentName = '';

    public string $equipmentArticleNumber = '';

    public ?int $equipmentCategoryId = null;

    public string $equipmentLink = '';

    // Category form fields
    public string $categoryName = '';

    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    #[Computed]
    public function equipment(): Collection
    {
        $query = EquipmentModel::query()->with('category');

        if ($this->selectedCategory !== null) {
            $query->where('category_id', (int) $this->selectedCategory);
        }

        if ($this->search !== '') {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('article_number', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->orderBy('type')->orderBy('name')->get();
    }

    public function selectCategory(?string $categoryId): void
    {
        $this->selectedCategory = $categoryId;
        unset($this->equipment);
    }

    public function updatedSearch(): void
    {
        unset($this->equipment);
    }

    public function openEquipmentModal(?int $id = null): void
    {
        $this->editingEquipmentId = $id;

        if ($id) {
            $item = EquipmentModel::find($id);
            if ($item) {
                $this->equipmentType = $item->type;
                $this->equipmentName = $item->name;
                $this->equipmentArticleNumber = $item->article_number ?? '';
                $this->equipmentCategoryId = $item->category_id;
                $this->equipmentLink = $item->link ?? '';
            }
        } else {
            $this->resetEquipmentForm();
        }

        $this->showEquipmentModal = true;
    }

    public function closeEquipmentModal(): void
    {
        $this->showEquipmentModal = false;
        $this->resetEquipmentForm();
    }

    public function saveEquipment(): void
    {
        $validated = $this->validate([
            'equipmentType' => 'required|string|max:255',
            'equipmentName' => 'required|string|max:255',
            'equipmentArticleNumber' => 'nullable|string|max:255',
            'equipmentCategoryId' => 'required|exists:categories,id',
            'equipmentLink' => 'nullable|string|max:255',
        ]);

        $data = [
            'type' => $validated['equipmentType'],
            'name' => $validated['equipmentName'],
            'article_number' => $validated['equipmentArticleNumber'] ?: null,
            'category_id' => $validated['equipmentCategoryId'],
            'link' => $validated['equipmentLink'] ?: null,
        ];

        if ($this->editingEquipmentId) {
            EquipmentModel::find($this->editingEquipmentId)?->update($data);
            $this->dispatch('toast', type: 'success', message: 'Utstyret ble oppdatert');
        } else {
            EquipmentModel::create($data);
            $this->dispatch('toast', type: 'success', message: 'Utstyret ble lagt til');
        }

        unset($this->equipment);
        $this->closeEquipmentModal();
    }

    public function deleteEquipment(int $id): void
    {
        EquipmentModel::find($id)?->delete();
        unset($this->equipment);
        $this->dispatch('toast', type: 'success', message: 'Utstyret ble slettet');
    }

    public function openCategoryModal(): void
    {
        $this->editingCategoryId = null;
        $this->categoryName = '';
        $this->showCategoryModal = true;
    }

    public function closeCategoryModal(): void
    {
        $this->showCategoryModal = false;
        $this->editingCategoryId = null;
        $this->categoryName = '';
        $this->resetValidation();
    }

    public function editCategory(int $id): void
    {
        $category = Category::find($id);
        if ($category) {
            $this->editingCategoryId = $id;
            $this->categoryName = $category->name;
        }
    }

    public function saveCategory(): void
    {
        $validated = $this->validate([
            'categoryName' => 'required|string|max:255',
        ]);

        if ($this->editingCategoryId) {
            Category::find($this->editingCategoryId)?->update(['name' => $validated['categoryName']]);
            $this->dispatch('toast', type: 'success', message: 'Kategorien ble oppdatert');
        } else {
            Category::create(['name' => $validated['categoryName']]);
            $this->dispatch('toast', type: 'success', message: 'Kategorien ble opprettet');
        }

        unset($this->categories);
        $this->editingCategoryId = null;
        $this->categoryName = '';
        $this->resetValidation();
    }

    public function deleteCategory(int $id): void
    {
        Category::find($id)?->delete();
        unset($this->categories);
        unset($this->equipment);
        $this->dispatch('toast', type: 'success', message: 'Kategorien ble slettet');
    }

    private function resetEquipmentForm(): void
    {
        $this->editingEquipmentId = null;
        $this->equipmentType = '';
        $this->equipmentName = '';
        $this->equipmentArticleNumber = '';
        $this->equipmentCategoryId = null;
        $this->equipmentLink = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.medical.equipment');
    }
}
