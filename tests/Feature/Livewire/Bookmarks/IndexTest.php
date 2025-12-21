<?php

use App\Jobs\CheckDeadBookmarksJob;
use App\Livewire\Bookmarks\Index;
use App\Models\Bookmark;
use App\Models\BookmarkFolder;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// ====================
// Page Access
// ====================

test('can access bookmarks page', function () {
    $this->get(route('tools.bookmarks'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('guests cannot access bookmarks page', function () {
    auth()->logout();

    $this->get(route('tools.bookmarks'))
        ->assertRedirect(route('login'));
});

// ====================
// Bookmark CRUD
// ====================

test('can create a bookmark', function () {
    Livewire::test(Index::class)
        ->call('openBookmarkModal')
        ->assertSet('showBookmarkModal', true)
        ->set('bookmarkUrl', 'https://example.com')
        ->set('bookmarkTitle', 'Example Site')
        ->set('bookmarkDescription', 'A test bookmark')
        ->call('saveBookmark')
        ->assertSet('showBookmarkModal', false)
        ->assertDispatched('toast');

    $this->assertDatabaseHas('bookmarks', [
        'url' => 'https://example.com',
        'title' => 'Example Site',
        'description' => 'A test bookmark',
    ]);
});

test('can update a bookmark', function () {
    $bookmark = Bookmark::factory()->create([
        'url' => 'https://old.com',
        'title' => 'Old Title',
    ]);

    Livewire::test(Index::class)
        ->call('openBookmarkModal', $bookmark->id)
        ->assertSet('editingBookmarkId', $bookmark->id)
        ->assertSet('bookmarkUrl', 'https://old.com')
        ->set('bookmarkUrl', 'https://new.com')
        ->set('bookmarkTitle', 'New Title')
        ->call('saveBookmark')
        ->assertDispatched('toast');

    $this->assertDatabaseHas('bookmarks', [
        'id' => $bookmark->id,
        'url' => 'https://new.com',
        'title' => 'New Title',
    ]);
});

test('can delete a bookmark', function () {
    $bookmark = Bookmark::factory()->create();

    Livewire::test(Index::class)
        ->call('deleteBookmark', $bookmark->id)
        ->assertDispatched('toast');

    $this->assertDatabaseMissing('bookmarks', [
        'id' => $bookmark->id,
    ]);
});

test('cannot create duplicate url', function () {
    Bookmark::factory()->create(['url' => 'https://example.com']);

    Livewire::test(Index::class)
        ->call('openBookmarkModal')
        ->set('bookmarkUrl', 'https://example.com')
        ->set('bookmarkTitle', 'Another Title')
        ->call('saveBookmark')
        ->assertHasErrors('bookmarkUrl')
        ->assertSet('showBookmarkModal', true);
});

test('can toggle read status', function () {
    $bookmark = Bookmark::factory()->create(['is_read' => false]);

    Livewire::test(Index::class)
        ->call('toggleRead', $bookmark->id);

    $this->assertTrue($bookmark->fresh()->is_read);

    Livewire::test(Index::class)
        ->call('toggleRead', $bookmark->id);

    $this->assertFalse($bookmark->fresh()->is_read);
});

// ====================
// Folder CRUD
// ====================

test('can create a folder', function () {
    Livewire::test(Index::class)
        ->call('openFolderModal')
        ->assertSet('showFolderModal', true)
        ->set('folderName', 'Test Folder')
        ->call('saveFolder')
        ->assertSet('showFolderModal', false)
        ->assertDispatched('toast');

    $this->assertDatabaseHas('bookmark_folders', [
        'name' => 'Test Folder',
    ]);
});

test('can update a folder', function () {
    $folder = BookmarkFolder::factory()->create(['name' => 'Old Name']);

    Livewire::test(Index::class)
        ->call('openFolderModal', $folder->id)
        ->assertSet('editingFolderId', $folder->id)
        ->set('folderName', 'New Name')
        ->call('saveFolder');

    $this->assertDatabaseHas('bookmark_folders', [
        'id' => $folder->id,
        'name' => 'New Name',
    ]);
});

test('can delete folder keeping bookmarks', function () {
    $folder = BookmarkFolder::factory()->create();
    $bookmark = Bookmark::factory()->create(['folder_id' => $folder->id]);

    Livewire::test(Index::class)
        ->call('deleteFolder', $folder->id);

    $this->assertDatabaseMissing('bookmark_folders', ['id' => $folder->id]);
    $this->assertDatabaseHas('bookmarks', ['id' => $bookmark->id, 'folder_id' => null]);
});

test('can delete folder with bookmarks', function () {
    $folder = BookmarkFolder::factory()->create();
    $bookmark = Bookmark::factory()->create(['folder_id' => $folder->id]);

    Livewire::test(Index::class)
        ->call('deleteFolderWithBookmarks', $folder->id);

    $this->assertDatabaseMissing('bookmark_folders', ['id' => $folder->id]);
    $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
});

test('can set folder as default', function () {
    $folder = BookmarkFolder::factory()->create(['is_default' => false]);

    Livewire::test(Index::class)
        ->call('openFolderModal', $folder->id)
        ->set('folderIsDefault', true)
        ->call('saveFolder');

    $this->assertTrue($folder->fresh()->is_default);
});

// ====================
// Search, Filter, Sort
// ====================

test('can search bookmarks', function () {
    Bookmark::factory()->create(['title' => 'Laravel Documentation']);
    Bookmark::factory()->create(['title' => 'Vue.js Guide']);

    Livewire::test(Index::class)
        ->set('search', 'Laravel')
        ->assertSee('Laravel Documentation')
        ->assertDontSee('Vue.js Guide');
});

test('can filter by folder', function () {
    $folder = BookmarkFolder::factory()->create(['name' => 'Dev Tools']);
    Bookmark::factory()->create(['folder_id' => $folder->id, 'title' => 'In Folder Bookmark']);
    Bookmark::factory()->create(['folder_id' => null, 'title' => 'No Folder Bookmark']);

    Livewire::test(Index::class)
        ->set('folderId', $folder->id)
        ->assertSee('In Folder Bookmark')
        ->assertDontSee('No Folder Bookmark');
});

test('can sort bookmarks', function () {
    Bookmark::factory()->create(['title' => 'Alpha', 'created_at' => now()->subDays(2)]);
    Bookmark::factory()->create(['title' => 'Beta', 'created_at' => now()]);

    // Default: newest first - verify both are visible
    Livewire::test(Index::class)
        ->assertSee('Alpha')
        ->assertSee('Beta');

    // A-Z sorting
    Livewire::test(Index::class)
        ->set('sortBy', 'title_asc')
        ->assertSeeInOrder(['Alpha', 'Beta']);

    // Z-A sorting
    Livewire::test(Index::class)
        ->set('sortBy', 'title_desc')
        ->assertSeeInOrder(['Beta', 'Alpha']);
});

test('can clear search', function () {
    Livewire::test(Index::class)
        ->set('search', 'test')
        ->call('clearSearch')
        ->assertSet('search', '');
});

// ====================
// Bulk Operations
// ====================

test('can select all bookmarks', function () {
    $bookmarks = Bookmark::factory()->count(3)->create();

    // Setting selectAll triggers the updatedSelectAll lifecycle hook automatically
    $component = Livewire::test(Index::class)
        ->set('selectAll', true);

    expect($component->get('selectedIds'))->toHaveCount(3);
});

test('can bulk delete bookmarks', function () {
    $bookmarks = Bookmark::factory()->count(3)->create();

    Livewire::test(Index::class)
        ->set('selectedIds', $bookmarks->pluck('id')->toArray())
        ->call('bulkDelete')
        ->assertDispatched('toast');

    $this->assertDatabaseCount('bookmarks', 0);
});

test('can bulk move bookmarks to folder', function () {
    $folder = BookmarkFolder::factory()->create();
    $bookmarks = Bookmark::factory()->count(3)->create(['folder_id' => null]);

    Livewire::test(Index::class)
        ->set('selectedIds', $bookmarks->pluck('id')->toArray())
        ->call('openMoveModal')
        ->assertSet('showMoveModal', true)
        ->set('moveToFolderId', $folder->id)
        ->call('bulkMove')
        ->assertDispatched('toast');

    foreach ($bookmarks as $bookmark) {
        $this->assertEquals($folder->id, $bookmark->fresh()->folder_id);
    }
});

// ====================
// Dead Link Check
// ====================

test('can trigger dead link check for all', function () {
    Queue::fake();
    Bookmark::factory()->count(2)->create();

    Livewire::test(Index::class)
        ->call('checkDeadLinks')
        ->assertDispatched('toast');

    Queue::assertPushed(CheckDeadBookmarksJob::class);
});

test('can trigger dead link check for single bookmark', function () {
    Queue::fake();
    $bookmark = Bookmark::factory()->create();

    Livewire::test(Index::class)
        ->call('checkSingleDeadLink', $bookmark->id)
        ->assertDispatched('toast');

    Queue::assertPushed(CheckDeadBookmarksJob::class, function ($job) use ($bookmark) {
        return $job->bookmarkId === $bookmark->id;
    });
});

test('can clear dead status', function () {
    $bookmark = Bookmark::factory()->create(['is_dead' => true]);

    Livewire::test(Index::class)
        ->call('clearDeadStatus', $bookmark->id);

    $this->assertFalse($bookmark->fresh()->is_dead);
});

// ====================
// Move to Wishlist
// ====================

test('can dispatch move to wishlist event', function () {
    $bookmark = Bookmark::factory()->create([
        'url' => 'https://example.com',
        'title' => 'Example',
        'description' => 'A description',
    ]);

    Livewire::test(Index::class)
        ->call('moveToWishlist', $bookmark->id)
        ->assertDispatched('open-wishlist-modal', [
            'url' => 'https://example.com',
            'name' => 'Example',
            'notes' => 'A description',
        ]);
});

// ====================
// Move Bookmark to Folder
// ====================

test('can move bookmark to folder', function () {
    $folder = BookmarkFolder::factory()->create();
    $bookmark = Bookmark::factory()->create(['folder_id' => null]);

    Livewire::test(Index::class)
        ->call('moveToFolder', $bookmark->id, $folder->id);

    $this->assertEquals($folder->id, $bookmark->fresh()->folder_id);
});
