<?php

use App\Livewire\Bookmarks\QuickAdd;
use App\Models\Bookmark;
use App\Models\BookmarkFolder;
use App\Models\User;

// ====================
// Token Authentication
// ====================

test('can access with valid token', function () {
    $user = User::factory()->create();
    $token = $user->ensureBookmarkToken();

    $this->get(route('tools.bookmarks.add', ['token' => $token]))
        ->assertOk()
        ->assertSeeLivewire(QuickAdd::class);
});

test('returns 404 with invalid token', function () {
    $this->get(route('tools.bookmarks.add', ['token' => 'invalid-token']))
        ->assertNotFound();
});

test('returns 404 without token or session', function () {
    $this->get(route('tools.bookmarks.add'))
        ->assertNotFound();
});

// ====================
// Session Authentication
// ====================

test('can access with session authentication', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('tools.bookmarks.add'))
        ->assertOk()
        ->assertSeeLivewire(QuickAdd::class);
});

test('session auth works with invalid token', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Invalid token but logged in - should work via session
    $this->get(route('tools.bookmarks.add', ['token' => 'invalid-token']))
        ->assertOk();
});

// ====================
// Query Parameters via HTTP
// ====================

test('populates url from query parameter', function () {
    $user = User::factory()->create();
    $token = $user->ensureBookmarkToken();

    $this->get(route('tools.bookmarks.add', [
        'token' => $token,
        'url' => 'https://example.com',
        'title' => 'Test Title', // Prevent auto-fetch
    ]))
        ->assertOk()
        ->assertSee('https://example.com');
});

test('populates title from query parameter', function () {
    $user = User::factory()->create();
    $token = $user->ensureBookmarkToken();

    $this->get(route('tools.bookmarks.add', [
        'token' => $token,
        'title' => 'Example Site',
    ]))
        ->assertOk()
        ->assertSee('Example Site');
});

// ====================
// Default Folder
// ====================

test('selects default folder on mount', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $defaultFolder = BookmarkFolder::factory()->create(['is_default' => true]);
    BookmarkFolder::factory()->create(['is_default' => false]);

    $this->get(route('tools.bookmarks.add'))
        ->assertOk()
        ->assertSee($defaultFolder->name);
});

// ====================
// Saving Bookmark
// ====================

test('can save a bookmark', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire\Livewire::withQueryParams([])
        ->test(QuickAdd::class)
        ->set('url', 'https://example.com')
        ->set('title', 'Example Site')
        ->set('description', 'A test')
        ->call('save')
        ->assertSet('isSaved', true);

    $this->assertDatabaseHas('bookmarks', [
        'url' => 'https://example.com',
        'title' => 'Example Site',
        'description' => 'A test',
    ]);
});

test('can save bookmark to folder', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $folder = BookmarkFolder::factory()->create();

    Livewire\Livewire::withQueryParams([])
        ->test(QuickAdd::class)
        ->set('url', 'https://example.com')
        ->set('title', 'Example Site')
        ->set('folderId', $folder->id)
        ->call('save')
        ->assertSet('isSaved', true);

    $this->assertDatabaseHas('bookmarks', [
        'url' => 'https://example.com',
        'folder_id' => $folder->id,
    ]);
});

test('validates required fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire\Livewire::withQueryParams([])
        ->test(QuickAdd::class)
        ->set('url', '')
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['url', 'title']);
});

test('validates url format', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire\Livewire::withQueryParams([])
        ->test(QuickAdd::class)
        ->set('url', 'not-a-url')
        ->set('title', 'Test')
        ->call('save')
        ->assertHasErrors('url');
});

// ====================
// Duplicate URL Check
// ====================

test('detects duplicate url', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Bookmark::factory()->create(['url' => 'https://duplicate.com']);

    Livewire\Livewire::withQueryParams([])
        ->test(QuickAdd::class)
        ->set('url', 'https://duplicate.com')
        ->set('title', 'Duplicate Test')
        ->call('save')
        ->assertSet('duplicateUrl', 'https://duplicate.com')
        ->assertSet('isSaved', false);
});

// ====================
// Add Another
// ====================

test('can add another bookmark', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire\Livewire::withQueryParams([])
        ->test(QuickAdd::class)
        ->set('url', 'https://example.com')
        ->set('title', 'Example')
        ->call('save')
        ->assertSet('isSaved', true)
        ->call('addAnother')
        ->assertSet('isSaved', false)
        ->assertSet('url', '')
        ->assertSet('title', '')
        ->assertSet('description', '');
});

// ====================
// Sort Order
// ====================

test('assigns correct sort order', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create existing bookmark with sort_order 5
    Bookmark::factory()->create(['sort_order' => 5, 'folder_id' => null]);

    Livewire\Livewire::withQueryParams([])
        ->test(QuickAdd::class)
        ->set('url', 'https://new.com')
        ->set('title', 'New Bookmark')
        ->call('save');

    $newBookmark = Bookmark::where('url', 'https://new.com')->first();
    expect($newBookmark->sort_order)->toBe(6);
});

test('assigns correct sort order within folder', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $folder = BookmarkFolder::factory()->create();

    // Create existing bookmark in folder with sort_order 3
    Bookmark::factory()->create(['sort_order' => 3, 'folder_id' => $folder->id]);

    // Create bookmark outside folder (should not affect sort order)
    Bookmark::factory()->create(['sort_order' => 10, 'folder_id' => null]);

    Livewire\Livewire::withQueryParams([])
        ->test(QuickAdd::class)
        ->set('url', 'https://new.com')
        ->set('title', 'New Bookmark')
        ->set('folderId', $folder->id)
        ->call('save');

    $newBookmark = Bookmark::where('url', 'https://new.com')->first();
    expect($newBookmark->sort_order)->toBe(4);
});
