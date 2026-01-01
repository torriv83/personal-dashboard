<?php

declare(strict_types=1);

use App\Livewire\Medical\Hjelpemidler;
use App\Models\Hjelpemiddel;
use App\Models\HjelpemiddelKategori;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the hjelpemidler page', function () {
    $this->get(route('medical.hjelpemidler'))
        ->assertOk()
        ->assertSee('Hjelpemidler');
});

it('displays all hjelpemidler', function () {
    $kategori = HjelpemiddelKategori::factory()->create();
    Hjelpemiddel::factory()->create(['name' => 'Test Rullestol', 'hjelpemiddel_kategori_id' => $kategori->id]);
    Hjelpemiddel::factory()->create(['name' => 'Test Krykke', 'hjelpemiddel_kategori_id' => $kategori->id]);

    Livewire::test(Hjelpemidler::class)
        ->assertSee('Test Rullestol')
        ->assertSee('Test Krykke');
});

it('displays kategorier', function () {
    HjelpemiddelKategori::factory()->create(['name' => 'Kategori A']);
    HjelpemiddelKategori::factory()->create(['name' => 'Kategori B']);

    Livewire::test(Hjelpemidler::class)
        ->assertSee('Kategori A')
        ->assertSee('Kategori B');
});

it('can create new hjelpemiddel', function () {
    $kategori = HjelpemiddelKategori::factory()->create();

    Livewire::test(Hjelpemidler::class)
        ->call('openItemModal')
        ->assertSet('showItemModal', true)
        ->set('itemName', 'Ny Rullestol')
        ->set('itemUrl', 'https://example.com/rullestol')
        ->set('editingItemKategoriId', $kategori->id)
        ->call('saveItem')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('hjelpemidler', [
        'name' => 'Ny Rullestol',
        'url' => 'https://example.com/rullestol',
        'hjelpemiddel_kategori_id' => $kategori->id,
    ]);
});

it('validates required fields when creating hjelpemiddel', function () {
    Livewire::test(Hjelpemidler::class)
        ->call('openItemModal')
        ->set('itemName', '')
        ->set('editingItemKategoriId', null)
        ->call('saveItem')
        ->assertHasErrors(['itemName', 'editingItemKategoriId']);
});

it('can edit existing hjelpemiddel', function () {
    $kategori = HjelpemiddelKategori::factory()->create();
    $hjelpemiddel = Hjelpemiddel::factory()->create([
        'name' => 'Original Name',
        'url' => 'https://example.com/original',
        'hjelpemiddel_kategori_id' => $kategori->id,
    ]);

    Livewire::test(Hjelpemidler::class)
        ->call('openItemModal', $hjelpemiddel->id)
        ->assertSet('editingItemId', $hjelpemiddel->id)
        ->assertSet('itemName', 'Original Name')
        ->assertSet('itemUrl', 'https://example.com/original')
        ->set('itemName', 'Updated Name')
        ->set('itemUrl', 'https://example.com/updated')
        ->call('saveItem')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('hjelpemidler', [
        'id' => $hjelpemiddel->id,
        'name' => 'Updated Name',
        'url' => 'https://example.com/updated',
    ]);
});

it('can delete hjelpemiddel', function () {
    $kategori = HjelpemiddelKategori::factory()->create();
    $hjelpemiddel = Hjelpemiddel::factory()->create([
        'name' => 'To Delete',
        'hjelpemiddel_kategori_id' => $kategori->id,
    ]);

    Livewire::test(Hjelpemidler::class)
        ->call('deleteItem', $hjelpemiddel->id);

    $this->assertDatabaseMissing('hjelpemidler', [
        'id' => $hjelpemiddel->id,
    ]);
});

it('can create new kategori', function () {
    Livewire::test(Hjelpemidler::class)
        ->call('openKategoriModal')
        ->assertSet('showKategoriModal', true)
        ->set('kategoriName', 'Ny Kategori')
        ->call('saveKategori')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('hjelpemiddel_kategorier', [
        'name' => 'Ny Kategori',
    ]);
});

it('validates required fields when creating kategori', function () {
    Livewire::test(Hjelpemidler::class)
        ->call('openKategoriModal')
        ->set('kategoriName', '')
        ->call('saveKategori')
        ->assertHasErrors(['kategoriName']);
});

it('can edit existing kategori', function () {
    $kategori = HjelpemiddelKategori::factory()->create(['name' => 'Original Kategori']);

    Livewire::test(Hjelpemidler::class)
        ->call('openKategoriModal', $kategori->id)
        ->assertSet('editingKategoriId', $kategori->id)
        ->assertSet('kategoriName', 'Original Kategori')
        ->set('kategoriName', 'Updated Kategori')
        ->call('saveKategori')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('hjelpemiddel_kategorier', [
        'id' => $kategori->id,
        'name' => 'Updated Kategori',
    ]);
});

it('can delete kategori', function () {
    $kategori = HjelpemiddelKategori::factory()->create(['name' => 'To Delete']);
    Hjelpemiddel::factory()->create([
        'name' => 'Item in Category',
        'hjelpemiddel_kategori_id' => $kategori->id,
    ]);

    Livewire::test(Hjelpemidler::class)
        ->call('deleteKategori', $kategori->id);

    $this->assertDatabaseMissing('hjelpemiddel_kategorier', [
        'id' => $kategori->id,
    ]);

    $this->assertDatabaseMissing('hjelpemidler', [
        'name' => 'Item in Category',
    ]);
});

it('can add custom field', function () {
    Livewire::test(Hjelpemidler::class)
        ->call('openItemModal')
        ->assertSet('itemCustomFields', [])
        ->call('addCustomField')
        ->assertSet('itemCustomFields', [['key' => '', 'value' => '']])
        ->call('addCustomField')
        ->assertCount('itemCustomFields', 2);
});

it('can remove custom field', function () {
    Livewire::test(Hjelpemidler::class)
        ->call('openItemModal')
        ->call('addCustomField')
        ->call('addCustomField')
        ->call('addCustomField')
        ->assertCount('itemCustomFields', 3)
        ->call('removeCustomField', 1)
        ->assertCount('itemCustomFields', 2);
});

it('can create hjelpemiddel with custom fields', function () {
    $kategori = HjelpemiddelKategori::factory()->create();

    Livewire::test(Hjelpemidler::class)
        ->call('openItemModal')
        ->set('itemName', 'Hjelpemiddel with Custom Fields')
        ->set('itemUrl', 'https://example.com/custom')
        ->set('editingItemKategoriId', $kategori->id)
        ->call('addCustomField')
        ->set('itemCustomFields.0.key', 'Farge')
        ->set('itemCustomFields.0.value', 'Blå')
        ->call('addCustomField')
        ->set('itemCustomFields.1.key', 'Størrelse')
        ->set('itemCustomFields.1.value', 'Medium')
        ->call('saveItem')
        ->assertHasNoErrors();

    $hjelpemiddel = Hjelpemiddel::where('name', 'Hjelpemiddel with Custom Fields')->first();
    expect($hjelpemiddel)->not->toBeNull();
    expect($hjelpemiddel->custom_fields)->toBe([
        ['key' => 'Farge', 'value' => 'Blå'],
        ['key' => 'Størrelse', 'value' => 'Medium'],
    ]);
});

it('can edit hjelpemiddel and modify custom fields', function () {
    $kategori = HjelpemiddelKategori::factory()->create();
    $hjelpemiddel = Hjelpemiddel::factory()->create([
        'name' => 'Original Item',
        'hjelpemiddel_kategori_id' => $kategori->id,
        'custom_fields' => [
            ['key' => 'Original Key', 'value' => 'Original Value'],
        ],
    ]);

    Livewire::test(Hjelpemidler::class)
        ->call('openItemModal', $hjelpemiddel->id)
        ->assertSet('editingItemId', $hjelpemiddel->id)
        ->assertSet('itemCustomFields', [['key' => 'Original Key', 'value' => 'Original Value']])
        ->set('itemCustomFields.0.value', 'Updated Value')
        ->call('addCustomField')
        ->set('itemCustomFields.1.key', 'New Key')
        ->set('itemCustomFields.1.value', 'New Value')
        ->call('saveItem')
        ->assertHasNoErrors();

    $hjelpemiddel->refresh();
    expect($hjelpemiddel->custom_fields)->toBe([
        ['key' => 'Original Key', 'value' => 'Updated Value'],
        ['key' => 'New Key', 'value' => 'New Value'],
    ]);
});

it('validates custom fields require both key and value', function () {
    $kategori = HjelpemiddelKategori::factory()->create();

    Livewire::test(Hjelpemidler::class)
        ->call('openItemModal')
        ->set('itemName', 'Item with Invalid Fields')
        ->set('editingItemKategoriId', $kategori->id)
        ->call('addCustomField')
        ->set('itemCustomFields.0.key', 'Valid Key')
        ->set('itemCustomFields.0.value', 'Valid Value')
        ->call('addCustomField')
        ->set('itemCustomFields.1.key', 'Only Key')
        // Leave value empty
        ->call('saveItem')
        ->assertHasErrors(['itemCustomFields.1.value']);
});

it('can remove empty custom field before saving', function () {
    $kategori = HjelpemiddelKategori::factory()->create();

    Livewire::test(Hjelpemidler::class)
        ->call('openItemModal')
        ->set('itemName', 'Item with Removed Field')
        ->set('editingItemKategoriId', $kategori->id)
        ->call('addCustomField')
        ->set('itemCustomFields.0.key', 'Valid Key')
        ->set('itemCustomFields.0.value', 'Valid Value')
        ->call('addCustomField')
        ->call('removeCustomField', 1)
        ->call('saveItem')
        ->assertHasNoErrors();

    $hjelpemiddel = Hjelpemiddel::where('name', 'Item with Removed Field')->first();
    expect($hjelpemiddel->custom_fields)->toBe([
        ['key' => 'Valid Key', 'value' => 'Valid Value'],
    ]);
});

it('can open brukerpass modal and load current value', function () {
    App\Models\Setting::set('hjelpemidler_brukerpass', 'existing-pass-123');

    Livewire::test(Hjelpemidler::class)
        ->call('openBrukerpassModal')
        ->assertSet('showBrukerpassModal', true)
        ->assertSet('brukerpassValue', 'existing-pass-123');
});

it('can save brukerpass value', function () {
    Livewire::test(Hjelpemidler::class)
        ->call('openBrukerpassModal')
        ->set('brukerpassValue', 'new-brukerpass-456')
        ->call('saveBrukerpass')
        ->assertHasNoErrors();

    expect(App\Models\Setting::get('hjelpemidler_brukerpass'))->toBe('new-brukerpass-456');
});

it('can update existing brukerpass value', function () {
    App\Models\Setting::set('hjelpemidler_brukerpass', 'old-pass');

    Livewire::test(Hjelpemidler::class)
        ->call('openBrukerpassModal')
        ->assertSet('brukerpassValue', 'old-pass')
        ->set('brukerpassValue', 'updated-pass')
        ->call('saveBrukerpass')
        ->assertHasNoErrors();

    expect(App\Models\Setting::get('hjelpemidler_brukerpass'))->toBe('updated-pass');
});

it('can clear brukerpass value', function () {
    App\Models\Setting::set('hjelpemidler_brukerpass', 'some-pass');

    Livewire::test(Hjelpemidler::class)
        ->call('openBrukerpassModal')
        ->set('brukerpassValue', '')
        ->call('saveBrukerpass')
        ->assertHasNoErrors();

    expect(App\Models\Setting::get('hjelpemidler_brukerpass'))->toBe('');
});

it('validates brukerpass maximum length', function () {
    $longValue = str_repeat('a', 51); // 51 characters, exceeds max of 50

    Livewire::test(Hjelpemidler::class)
        ->call('openBrukerpassModal')
        ->set('brukerpassValue', $longValue)
        ->call('saveBrukerpass')
        ->assertHasErrors(['brukerpassValue']);
});

it('can close item modal', function () {
    Livewire::test(Hjelpemidler::class)
        ->call('openItemModal')
        ->assertSet('showItemModal', true)
        ->call('closeItemModal')
        ->assertSet('showItemModal', false);
});

it('can close kategori modal', function () {
    Livewire::test(Hjelpemidler::class)
        ->call('openKategoriModal')
        ->assertSet('showKategoriModal', true)
        ->call('closeKategoriModal')
        ->assertSet('showKategoriModal', false);
});

it('can close brukerpass modal', function () {
    Livewire::test(Hjelpemidler::class)
        ->call('openBrukerpassModal')
        ->assertSet('showBrukerpassModal', true)
        ->call('closeBrukerpassModal')
        ->assertSet('showBrukerpassModal', false);
});

it('can create child item under parent item', function () {
    $kategori = HjelpemiddelKategori::factory()->create();
    $parent = Hjelpemiddel::factory()->create([
        'name' => 'Parent Hjelpemiddel',
        'hjelpemiddel_kategori_id' => $kategori->id,
        'parent_id' => null,
    ]);

    Livewire::test(Hjelpemidler::class)
        ->call('openItemModal', null, null, $parent->id)
        ->assertSet('showItemModal', true)
        ->assertSet('editingItemParentId', $parent->id)
        ->assertSet('editingItemKategoriId', $kategori->id) // Should inherit from parent
        ->set('itemName', 'Child Hjelpemiddel')
        ->set('itemUrl', 'https://example.com/child')
        ->call('saveItem')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('hjelpemidler', [
        'name' => 'Child Hjelpemiddel',
        'url' => 'https://example.com/child',
        'parent_id' => $parent->id,
        'hjelpemiddel_kategori_id' => $kategori->id,
    ]);
});
