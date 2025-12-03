<?php

use App\Livewire\Medical\Equipment;
use App\Models\Category;
use App\Models\Equipment as EquipmentModel;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the equipment page', function () {
    $this->get(route('medical.equipment'))
        ->assertOk()
        ->assertSee('Utstyr');
});

it('displays all equipment', function () {
    $category = Category::factory()->create();
    EquipmentModel::factory()->create(['name' => 'Test Tape', 'category_id' => $category->id]);
    EquipmentModel::factory()->create(['name' => 'Test Plaster', 'category_id' => $category->id]);

    Livewire::test(Equipment::class)
        ->assertSee('Test Tape')
        ->assertSee('Test Plaster');
});

it('displays categories', function () {
    Category::factory()->create(['name' => 'Kategori A']);
    Category::factory()->create(['name' => 'Kategori B']);

    Livewire::test(Equipment::class)
        ->assertSee('Kategori A')
        ->assertSee('Kategori B');
});

it('can filter equipment by category', function () {
    $categoryA = Category::factory()->create(['name' => 'Kategori A']);
    $categoryB = Category::factory()->create(['name' => 'Kategori B']);

    EquipmentModel::factory()->create(['name' => 'Utstyr A', 'category_id' => $categoryA->id]);
    EquipmentModel::factory()->create(['name' => 'Utstyr B', 'category_id' => $categoryB->id]);

    Livewire::test(Equipment::class)
        ->call('selectCategory', $categoryA->id)
        ->assertSee('Utstyr A')
        ->assertDontSee('Utstyr B');
});

it('can search equipment by name', function () {
    $category = Category::factory()->create();
    EquipmentModel::factory()->create(['name' => 'Special Tape', 'category_id' => $category->id]);
    EquipmentModel::factory()->create(['name' => 'Regular Plaster', 'category_id' => $category->id]);

    Livewire::test(Equipment::class)
        ->set('search', 'Special')
        ->assertSee('Special Tape')
        ->assertDontSee('Regular Plaster');
});

it('can search equipment by article number', function () {
    $category = Category::factory()->create();
    EquipmentModel::factory()->create([
        'name' => 'Item With Number',
        'article_number' => '123456',
        'category_id' => $category->id,
    ]);
    EquipmentModel::factory()->create([
        'name' => 'Item Without Match',
        'article_number' => '999999',
        'category_id' => $category->id,
    ]);

    Livewire::test(Equipment::class)
        ->set('search', '123456')
        ->assertSee('Item With Number')
        ->assertDontSee('Item Without Match');
});

it('can create new equipment', function () {
    $category = Category::factory()->create();

    Livewire::test(Equipment::class)
        ->call('openEquipmentModal')
        ->assertSet('showEquipmentModal', true)
        ->set('equipmentType', 'Tape')
        ->set('equipmentName', 'Ny Tape')
        ->set('equipmentArticleNumber', '123456')
        ->set('equipmentCategoryId', $category->id)
        ->call('saveEquipment')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('equipment', [
        'type' => 'Tape',
        'name' => 'Ny Tape',
        'article_number' => '123456',
        'category_id' => $category->id,
    ]);
});

it('validates required fields when creating equipment', function () {
    Livewire::test(Equipment::class)
        ->call('openEquipmentModal')
        ->set('equipmentType', '')
        ->set('equipmentName', '')
        ->set('equipmentCategoryId', null)
        ->call('saveEquipment')
        ->assertHasErrors(['equipmentType', 'equipmentName', 'equipmentCategoryId']);
});

it('can edit existing equipment', function () {
    $category = Category::factory()->create();
    $equipment = EquipmentModel::factory()->create([
        'name' => 'Original Name',
        'category_id' => $category->id,
    ]);

    Livewire::test(Equipment::class)
        ->call('openEquipmentModal', $equipment->id)
        ->assertSet('editingEquipmentId', $equipment->id)
        ->set('equipmentName', 'Updated Name')
        ->call('saveEquipment')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('equipment', [
        'id' => $equipment->id,
        'name' => 'Updated Name',
    ]);
});

it('can delete equipment', function () {
    $category = Category::factory()->create();
    $equipment = EquipmentModel::factory()->create([
        'name' => 'To Delete',
        'category_id' => $category->id,
    ]);

    Livewire::test(Equipment::class)
        ->call('deleteEquipment', $equipment->id);

    $this->assertSoftDeleted('equipment', [
        'id' => $equipment->id,
    ]);
});

it('can close equipment modal', function () {
    Livewire::test(Equipment::class)
        ->call('openEquipmentModal')
        ->assertSet('showEquipmentModal', true)
        ->call('closeEquipmentModal')
        ->assertSet('showEquipmentModal', false);
});

it('can create new category', function () {
    Livewire::test(Equipment::class)
        ->call('openCategoryModal')
        ->assertSet('showCategoryModal', true)
        ->set('categoryName', 'Ny Kategori')
        ->call('saveCategory')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('categories', [
        'name' => 'Ny Kategori',
    ]);
});

it('validates required fields when creating category', function () {
    Livewire::test(Equipment::class)
        ->call('openCategoryModal')
        ->set('categoryName', '')
        ->call('saveCategory')
        ->assertHasErrors(['categoryName']);
});

it('can edit existing category', function () {
    $category = Category::factory()->create(['name' => 'Original Category']);

    Livewire::test(Equipment::class)
        ->call('editCategory', $category->id)
        ->assertSet('editingCategoryId', $category->id)
        ->assertSet('categoryName', 'Original Category')
        ->set('categoryName', 'Updated Category')
        ->call('saveCategory')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Updated Category',
    ]);
});

it('can delete category', function () {
    $category = Category::factory()->create(['name' => 'To Delete']);

    Livewire::test(Equipment::class)
        ->call('deleteCategory', $category->id);

    $this->assertSoftDeleted('categories', [
        'id' => $category->id,
    ]);
});

it('can close category modal', function () {
    Livewire::test(Equipment::class)
        ->call('openCategoryModal')
        ->assertSet('showCategoryModal', true)
        ->call('closeCategoryModal')
        ->assertSet('showCategoryModal', false);
});
