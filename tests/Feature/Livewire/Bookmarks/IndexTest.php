<?php

declare(strict_types=1);

use App\Jobs\CheckDeadBookmarksJob;
use App\Livewire\Bookmarks\Index;
use App\Models\Bookmark;
use App\Models\BookmarkFolder;
use App\Models\BookmarkTag;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

/**
 * Helper to check if a bookmark card is rendered in the grid.
 * This checks for the actual card element, not JSON data in script tags.
 */
function assertBookmarkCardVisible(\Livewire\Features\SupportTesting\Testable $component, string $title): void
{
    // Check for the bookmark card div with the title inside
    $html = $component->html();
    // Remove script tags to avoid matching JSON data
    $htmlWithoutScripts = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);

    expect($htmlWithoutScripts)->toContain($title);
}

function assertBookmarkCardNotVisible(\Livewire\Features\SupportTesting\Testable $component, string $title): void
{
    $html = $component->html();
    // Remove script tags to avoid matching JSON data
    $htmlWithoutScripts = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);

    expect($htmlWithoutScripts)->not->toContain($title);
}

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

    $component = Livewire::test(Index::class)
        ->set('search', 'Laravel');

    assertBookmarkCardVisible($component, 'Laravel Documentation');
    assertBookmarkCardNotVisible($component, 'Vue.js Guide');
});

test('can filter by folder', function () {
    $folder = BookmarkFolder::factory()->create(['name' => 'Dev Tools']);
    Bookmark::factory()->create(['folder_id' => $folder->id, 'title' => 'In Folder Bookmark']);
    Bookmark::factory()->create(['folder_id' => null, 'title' => 'No Folder Bookmark']);

    $component = Livewire::test(Index::class)
        ->set('folderId', $folder->id);

    assertBookmarkCardVisible($component, 'In Folder Bookmark');
    assertBookmarkCardNotVisible($component, 'No Folder Bookmark');
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

test('can bulk move bookmarks out of folder to root', function () {
    $folder = BookmarkFolder::factory()->create();
    $bookmarks = Bookmark::factory()->count(3)->create(['folder_id' => $folder->id]);

    // Navigate into folder first
    Livewire::test(Index::class)
        ->call('openFolder', $folder->id)
        ->set('selectedIds', $bookmarks->pluck('id')->toArray())
        ->call('openMoveModal')
        ->assertSet('showMoveModal', true)
        ->set('moveToFolderId', '') // Empty string = remove from folder
        ->call('bulkMove')
        ->assertDispatched('toast');

    foreach ($bookmarks as $bookmark) {
        $this->assertNull($bookmark->fresh()->folder_id);
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

test('clearing dead status also removes dead tag', function () {
    $deadTag = BookmarkTag::factory()->create(['name' => 'Død', 'color' => 'red']);
    $bookmark = Bookmark::factory()->create(['is_dead' => true]);
    $bookmark->tags()->attach($deadTag);

    expect($bookmark->tags()->where('name', 'Død')->exists())->toBeTrue();

    Livewire::test(Index::class)
        ->call('clearDeadStatus', $bookmark->id);

    $bookmark->refresh();
    expect($bookmark->is_dead)->toBeFalse();
    expect($bookmark->tags()->where('name', 'Død')->exists())->toBeFalse();
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

// ====================
// Tag CRUD
// ====================

test('can create a tag', function () {
    Livewire::test(Index::class)
        ->call('openTagModal')
        ->assertSet('showTagModal', true)
        ->set('tagName', 'Work')
        ->set('tagColor', '#ef4444')
        ->call('saveTag')
        ->assertSet('showTagModal', false)
        ->assertDispatched('toast');

    $this->assertDatabaseHas('bookmark_tags', [
        'name' => 'Work',
        'color' => '#ef4444',
    ]);
});

test('can update a tag', function () {
    $tag = BookmarkTag::factory()->create(['name' => 'Old Tag', 'color' => '#3b82f6']);

    Livewire::test(Index::class)
        ->call('openTagModal', $tag->id)
        ->assertSet('editingTagId', $tag->id)
        ->assertSet('tagName', 'Old Tag')
        ->set('tagName', 'New Tag')
        ->set('tagColor', '#22c55e')
        ->call('saveTag');

    $this->assertDatabaseHas('bookmark_tags', [
        'id' => $tag->id,
        'name' => 'New Tag',
        'color' => '#22c55e',
    ]);
});

test('can delete a tag', function () {
    $tag = BookmarkTag::factory()->create();

    Livewire::test(Index::class)
        ->call('openTagModal', $tag->id)
        ->call('deleteTag', $tag->id)
        ->assertSet('showTagModal', false)
        ->assertDispatched('toast');

    $this->assertDatabaseMissing('bookmark_tags', ['id' => $tag->id]);
});

test('can filter by tag', function () {
    $tag = BookmarkTag::factory()->create(['name' => 'Important']);
    $bookmarkWithTag = Bookmark::factory()->create(['title' => 'Tagged Bookmark']);
    $bookmarkWithTag->tags()->attach($tag->id);
    $bookmarkWithoutTag = Bookmark::factory()->create(['title' => 'Untagged Bookmark']);

    Livewire::test(Index::class)
        ->call('setTagFilter', $tag->id)
        ->assertSet('tagId', $tag->id)
        ->assertSee('Tagged Bookmark')
        ->assertDontSee('Untagged Bookmark');
});

test('can filter by tag across folders', function () {
    $tag = BookmarkTag::factory()->create(['name' => 'Work']);
    $folder1 = BookmarkFolder::factory()->create(['name' => 'Folder 1']);
    $folder2 = BookmarkFolder::factory()->create(['name' => 'Folder 2']);

    $bookmark1 = Bookmark::factory()->create(['title' => 'In Folder 1', 'folder_id' => $folder1->id]);
    $bookmark1->tags()->attach($tag->id);

    $bookmark2 = Bookmark::factory()->create(['title' => 'In Folder 2', 'folder_id' => $folder2->id]);
    $bookmark2->tags()->attach($tag->id);

    $standaloneWithTag = Bookmark::factory()->create(['title' => 'Standalone Tagged', 'folder_id' => null]);
    $standaloneWithTag->tags()->attach($tag->id);

    // When filtering by tag, should see ALL bookmarks with that tag regardless of folder
    Livewire::test(Index::class)
        ->call('setTagFilter', $tag->id)
        ->assertSee('In Folder 1')
        ->assertSee('In Folder 2')
        ->assertSee('Standalone Tagged');
});

test('can add tags to bookmark', function () {
    $tag = BookmarkTag::factory()->create();

    Livewire::test(Index::class)
        ->call('openBookmarkModal')
        ->set('bookmarkUrl', 'https://example.com')
        ->set('bookmarkTitle', 'Example')
        ->set('bookmarkTagIds', [$tag->id])
        ->call('saveBookmark');

    $bookmark = Bookmark::where('url', 'https://example.com')->first();
    expect($bookmark->tags)->toHaveCount(1);
    expect($bookmark->tags->first()->id)->toBe($tag->id);
});

test('can toggle bookmark tag from card', function () {
    $tag = BookmarkTag::factory()->create();
    $bookmark = Bookmark::factory()->create();

    // Initially no tags
    expect($bookmark->tags)->toHaveCount(0);

    // Toggle on
    Livewire::test(Index::class)
        ->call('toggleBookmarkTag', $bookmark->id, $tag->id);

    expect($bookmark->fresh()->tags)->toHaveCount(1);
    expect($bookmark->fresh()->tags->first()->id)->toBe($tag->id);

    // Toggle off
    Livewire::test(Index::class)
        ->call('toggleBookmarkTag', $bookmark->id, $tag->id);

    expect($bookmark->fresh()->tags)->toHaveCount(0);
});

// ====================
// Folder Navigation
// ====================

test('can navigate into folder', function () {
    $folder = BookmarkFolder::factory()->create(['name' => 'My Folder']);
    $bookmarkInFolder = Bookmark::factory()->create(['folder_id' => $folder->id, 'title' => 'Folder Bookmark']);
    $bookmarkOutside = Bookmark::factory()->create(['folder_id' => null, 'title' => 'Outside Bookmark']);

    Livewire::test(Index::class)
        ->call('openFolder', $folder->id)
        ->assertSet('folderId', $folder->id)
        ->assertSee('Folder Bookmark')
        ->assertDontSee('Outside Bookmark');
});

test('can go back from folder', function () {
    $folder = BookmarkFolder::factory()->create();

    Livewire::test(Index::class)
        ->call('openFolder', $folder->id)
        ->assertSet('folderId', $folder->id)
        ->call('goBack')
        ->assertSet('folderId', null);
});

test('main view shows only standalone bookmarks', function () {
    $folder = BookmarkFolder::factory()->create();
    Bookmark::factory()->create(['folder_id' => null, 'title' => 'Standalone Bookmark']);
    Bookmark::factory()->create(['folder_id' => $folder->id, 'title' => 'Folder Bookmark']);

    $component = Livewire::test(Index::class);

    assertBookmarkCardVisible($component, 'Standalone Bookmark');
    assertBookmarkCardNotVisible($component, 'Folder Bookmark');
});

test('search shows all matching bookmarks regardless of folder', function () {
    $folder = BookmarkFolder::factory()->create();
    Bookmark::factory()->create(['folder_id' => null, 'title' => 'Laravel Standalone']);
    Bookmark::factory()->create(['folder_id' => $folder->id, 'title' => 'Laravel In Folder']);

    $component = Livewire::test(Index::class)
        ->set('search', 'Laravel');

    assertBookmarkCardVisible($component, 'Laravel Standalone');
    assertBookmarkCardVisible($component, 'Laravel In Folder');
});

// ====================
// Folder Hierarchy
// ====================

test('can create subfolder under parent folder', function () {
    $parent = BookmarkFolder::factory()->create(['name' => 'Parent Folder']);

    Livewire::test(Index::class)
        ->call('openFolderModal', null, $parent->id)
        ->assertSet('folderParentId', $parent->id)
        ->set('folderName', 'Child Folder')
        ->call('saveFolder')
        ->assertDispatched('toast');

    $this->assertDatabaseHas('bookmark_folders', [
        'name' => 'Child Folder',
        'parent_id' => $parent->id,
    ]);
});

test('cannot create folder more than 2 levels deep', function () {
    $parent = BookmarkFolder::factory()->create(['name' => 'Parent']);
    $child = BookmarkFolder::factory()->withParent($parent)->create(['name' => 'Child']);

    Livewire::test(Index::class)
        ->call('openFolderModal')
        ->set('folderName', 'Grandchild')
        ->set('folderParentId', $child->id)
        ->call('saveFolder')
        ->assertHasErrors('folderParentId');

    $this->assertDatabaseMissing('bookmark_folders', [
        'name' => 'Grandchild',
    ]);
});

test('folder tree shows hierarchy correctly', function () {
    $parent = BookmarkFolder::factory()->create(['name' => 'Parent Folder']);
    $child = BookmarkFolder::factory()->withParent($parent)->create(['name' => 'Child Folder']);
    Bookmark::factory()->create(['folder_id' => $parent->id]);
    Bookmark::factory()->count(2)->create(['folder_id' => $child->id]);

    // Get folder tree directly from database query (same as computed property)
    $folderTree = BookmarkFolder::query()
        ->whereNull('parent_id')
        ->with(['children' => fn ($q) => $q->withCount('bookmarks')->orderBy('sort_order')])
        ->withCount('bookmarks')
        ->orderBy('sort_order')
        ->get();

    expect($folderTree)->toHaveCount(1);
    expect($folderTree->first()->name)->toBe('Parent Folder');
    expect($folderTree->first()->children)->toHaveCount(1);
    expect($folderTree->first()->children->first()->name)->toBe('Child Folder');
    expect($folderTree->first()->bookmarks_count)->toBe(1);
    expect($folderTree->first()->children->first()->bookmarks_count)->toBe(2);

    // Also verify the component renders hierarchy in sidebar
    Livewire::test(Index::class)
        ->assertSee('Parent Folder')
        ->assertSee('Child Folder');
});

test('deleting parent folder deletes children via cascade', function () {
    $parent = BookmarkFolder::factory()->create(['name' => 'Parent']);
    $child = BookmarkFolder::factory()->withParent($parent)->create(['name' => 'Child']);
    $parentBookmark = Bookmark::factory()->create(['folder_id' => $parent->id]);
    $childBookmark = Bookmark::factory()->create(['folder_id' => $child->id]);

    Livewire::test(Index::class)
        ->call('deleteFolder', $parent->id);

    // Both folders should be gone
    $this->assertDatabaseMissing('bookmark_folders', ['id' => $parent->id]);
    $this->assertDatabaseMissing('bookmark_folders', ['id' => $child->id]);

    // Both bookmarks should be moved to no folder
    $this->assertDatabaseHas('bookmarks', ['id' => $parentBookmark->id, 'folder_id' => null]);
    $this->assertDatabaseHas('bookmarks', ['id' => $childBookmark->id, 'folder_id' => null]);
});

test('deleting parent folder with bookmarks deletes all bookmarks', function () {
    $parent = BookmarkFolder::factory()->create(['name' => 'Parent']);
    $child = BookmarkFolder::factory()->withParent($parent)->create(['name' => 'Child']);
    $parentBookmark = Bookmark::factory()->create(['folder_id' => $parent->id]);
    $childBookmark = Bookmark::factory()->create(['folder_id' => $child->id]);

    Livewire::test(Index::class)
        ->call('deleteFolderWithBookmarks', $parent->id);

    // Both folders and bookmarks should be gone
    $this->assertDatabaseMissing('bookmark_folders', ['id' => $parent->id]);
    $this->assertDatabaseMissing('bookmark_folders', ['id' => $child->id]);
    $this->assertDatabaseMissing('bookmarks', ['id' => $parentBookmark->id]);
    $this->assertDatabaseMissing('bookmarks', ['id' => $childBookmark->id]);
});

test('can toggle folder expanded state', function () {
    $folder = BookmarkFolder::factory()->create();

    $component = Livewire::test(Index::class)
        ->assertSet('expandedFolders', [])
        ->call('toggleFolderExpanded', $folder->id);

    expect($component->get('expandedFolders'))->toContain($folder->id);

    $component->call('toggleFolderExpanded', $folder->id);

    expect($component->get('expandedFolders'))->not->toContain($folder->id);
});

test('cannot set folder as its own parent', function () {
    $folder = BookmarkFolder::factory()->create(['name' => 'Test Folder']);

    Livewire::test(Index::class)
        ->call('openFolderModal', $folder->id)
        ->set('folderParentId', $folder->id)
        ->call('saveFolder')
        ->assertHasErrors('folderParentId');
});

test('root folders computed shows only parent folders', function () {
    $parent1 = BookmarkFolder::factory()->create(['name' => 'Parent 1']);
    $parent2 = BookmarkFolder::factory()->create(['name' => 'Parent 2']);
    $child = BookmarkFolder::factory()->withParent($parent1)->create(['name' => 'Child']);

    // Get root folders directly from database query (same as computed property)
    $rootFolders = BookmarkFolder::query()
        ->whereNull('parent_id')
        ->orderBy('sort_order')
        ->get();

    expect($rootFolders)->toHaveCount(2);
    expect($rootFolders->pluck('name')->toArray())->toContain('Parent 1', 'Parent 2');
    expect($rootFolders->pluck('name')->toArray())->not->toContain('Child');
});

test('can reorder root folders via drag and drop', function () {
    $folder1 = BookmarkFolder::factory()->create(['name' => 'Folder 1', 'sort_order' => 0]);
    $folder2 = BookmarkFolder::factory()->create(['name' => 'Folder 2', 'sort_order' => 1]);
    $folder3 = BookmarkFolder::factory()->create(['name' => 'Folder 3', 'sort_order' => 2]);

    // Move folder3 to position 0 (first)
    Livewire::test(Index::class)
        ->call('updateFolderOrder', "folder-{$folder3->id}", 0);

    expect($folder3->fresh()->sort_order)->toBe(0);
    expect($folder1->fresh()->sort_order)->toBe(1);
    expect($folder2->fresh()->sort_order)->toBe(2);
});

test('can reorder subfolders via drag and drop', function () {
    $parent = BookmarkFolder::factory()->create(['name' => 'Parent']);
    $child1 = BookmarkFolder::factory()->withParent($parent)->create(['name' => 'Child 1', 'sort_order' => 0]);
    $child2 = BookmarkFolder::factory()->withParent($parent)->create(['name' => 'Child 2', 'sort_order' => 1]);
    $child3 = BookmarkFolder::factory()->withParent($parent)->create(['name' => 'Child 3', 'sort_order' => 2]);

    // Move child1 to position 2 (last)
    Livewire::test(Index::class)
        ->call('updateFolderOrder', "folder-{$child1->id}", 2);

    expect($child2->fresh()->sort_order)->toBe(0);
    expect($child3->fresh()->sort_order)->toBe(1);
    expect($child1->fresh()->sort_order)->toBe(2);
});

test('folder reorder only affects same level folders', function () {
    $root1 = BookmarkFolder::factory()->create(['name' => 'Root 1', 'sort_order' => 0]);
    $root2 = BookmarkFolder::factory()->create(['name' => 'Root 2', 'sort_order' => 1]);
    $child1 = BookmarkFolder::factory()->withParent($root1)->create(['name' => 'Child 1', 'sort_order' => 0]);
    $child2 = BookmarkFolder::factory()->withParent($root1)->create(['name' => 'Child 2', 'sort_order' => 1]);

    // Reorder root folders - should not affect children
    Livewire::test(Index::class)
        ->call('updateFolderOrder', "folder-{$root2->id}", 0);

    expect($root2->fresh()->sort_order)->toBe(0);
    expect($root1->fresh()->sort_order)->toBe(1);
    // Children should be unaffected
    expect($child1->fresh()->sort_order)->toBe(0);
    expect($child2->fresh()->sort_order)->toBe(1);
});

// ====================
// Drag Bookmark to Folder
// ====================

test('can drop bookmark onto folder to move it', function () {
    $folder = BookmarkFolder::factory()->create(['name' => 'Target Folder']);
    $bookmark = Bookmark::factory()->create(['folder_id' => null]);

    Livewire::test(Index::class)
        ->call('dropBookmarkToFolder', $bookmark->id, $folder->id)
        ->assertDispatched('toast');

    expect($bookmark->fresh()->folder_id)->toBe($folder->id);
});

test('can drop bookmark onto all bookmarks to remove from folder', function () {
    $folder = BookmarkFolder::factory()->create();
    $bookmark = Bookmark::factory()->create(['folder_id' => $folder->id]);

    Livewire::test(Index::class)
        ->call('dropBookmarkToFolder', $bookmark->id, null)
        ->assertDispatched('toast');

    expect($bookmark->fresh()->folder_id)->toBeNull();
});

test('dropping bookmark onto same folder does nothing', function () {
    $folder = BookmarkFolder::factory()->create();
    $bookmark = Bookmark::factory()->create(['folder_id' => $folder->id]);

    Livewire::test(Index::class)
        ->call('dropBookmarkToFolder', $bookmark->id, $folder->id)
        ->assertNotDispatched('toast');

    expect($bookmark->fresh()->folder_id)->toBe($folder->id);
});

// ====================
// Preview Modal
// ====================

test('can open preview modal for bookmark', function () {
    $bookmark = Bookmark::factory()->create([
        'url' => 'https://example.com',
        'title' => 'Example Site',
    ]);

    Livewire::test(Index::class)
        ->assertSet('showPreviewModal', false)
        ->call('openPreview', $bookmark->id)
        ->assertSet('showPreviewModal', true)
        ->assertSet('previewUrl', 'https://example.com')
        ->assertSet('previewTitle', 'Example Site');
});

test('can close preview modal', function () {
    $bookmark = Bookmark::factory()->create([
        'url' => 'https://example.com',
        'title' => 'Example Site',
    ]);

    Livewire::test(Index::class)
        ->call('openPreview', $bookmark->id)
        ->assertSet('showPreviewModal', true)
        ->call('closePreview')
        ->assertSet('showPreviewModal', false)
        ->assertSet('previewUrl', '')
        ->assertSet('previewTitle', '');
});

// ====================
// Mobile Folder Sidebar
// ====================

test('can toggle mobile folder sidebar', function () {
    Livewire::test(Index::class)
        ->assertSet('showMobileFolderSidebar', false)
        ->call('toggleMobileFolderSidebar')
        ->assertSet('showMobileFolderSidebar', true)
        ->call('toggleMobileFolderSidebar')
        ->assertSet('showMobileFolderSidebar', false);
});

test('can close mobile folder sidebar', function () {
    Livewire::test(Index::class)
        ->call('toggleMobileFolderSidebar')
        ->assertSet('showMobileFolderSidebar', true)
        ->call('closeMobileFolderSidebar')
        ->assertSet('showMobileFolderSidebar', false);
});

test('opening folder closes mobile sidebar', function () {
    $folder = BookmarkFolder::factory()->create();

    Livewire::test(Index::class)
        ->call('toggleMobileFolderSidebar')
        ->assertSet('showMobileFolderSidebar', true)
        ->call('openFolder', $folder->id)
        ->assertSet('showMobileFolderSidebar', false)
        ->assertSet('folderId', $folder->id);
});

// ====================
// Pinned Bookmarks
// ====================

test('can toggle pin status on bookmark', function () {
    $bookmark = Bookmark::factory()->create(['is_pinned' => false]);

    Livewire::test(Index::class)
        ->call('togglePin', $bookmark->id);

    expect($bookmark->fresh()->is_pinned)->toBeTrue();
    expect($bookmark->fresh()->pinned_order)->toBe(0);

    Livewire::test(Index::class)
        ->call('togglePin', $bookmark->id);

    expect($bookmark->fresh()->is_pinned)->toBeFalse();
    expect($bookmark->fresh()->pinned_order)->toBeNull();
});

test('pinned bookmarks get sequential order', function () {
    $bookmark1 = Bookmark::factory()->create();
    $bookmark2 = Bookmark::factory()->create();
    $bookmark3 = Bookmark::factory()->create();

    Livewire::test(Index::class)->call('togglePin', $bookmark1->id);
    Livewire::test(Index::class)->call('togglePin', $bookmark2->id);
    Livewire::test(Index::class)->call('togglePin', $bookmark3->id);

    expect($bookmark1->fresh()->pinned_order)->toBe(0);
    expect($bookmark2->fresh()->pinned_order)->toBe(1);
    expect($bookmark3->fresh()->pinned_order)->toBe(2);
});

test('pinned bookmarks shown on main view', function () {
    $pinnedBookmark = Bookmark::factory()->pinned(0)->create(['title' => 'Pinned Bookmark']);
    $regularBookmark = Bookmark::factory()->create(['title' => 'Regular Bookmark']);

    $component = Livewire::test(Index::class);

    // Both should be visible on main view
    assertBookmarkCardVisible($component, 'Pinned Bookmark');
    assertBookmarkCardVisible($component, 'Regular Bookmark');

    // Pinned section header should be visible
    $component->assertSee('Festede');
});

test('pinned section not shown when in folder', function () {
    $folder = BookmarkFolder::factory()->create();
    $pinnedBookmark = Bookmark::factory()->pinned(0)->create(['folder_id' => $folder->id, 'title' => 'Pinned In Folder']);

    $component = Livewire::test(Index::class)
        ->call('openFolder', $folder->id);

    // Should see the bookmark but not the pinned section header
    $component->assertDontSee('Festede');
});

test('pinned section not shown when searching', function () {
    $pinnedBookmark = Bookmark::factory()->pinned(0)->create(['title' => 'Pinned Bookmark']);

    $component = Livewire::test(Index::class)
        ->set('search', 'Pinned');

    // Should find the bookmark but not show pinned section
    assertBookmarkCardVisible($component, 'Pinned Bookmark');
    $component->assertDontSee('Festede');
});

test('pinned section not shown when filtering by tag', function () {
    $tag = BookmarkTag::factory()->create();
    $pinnedBookmark = Bookmark::factory()->pinned(0)->create(['title' => 'Pinned Tagged']);
    $pinnedBookmark->tags()->attach($tag->id);

    $component = Livewire::test(Index::class)
        ->call('setTagFilter', $tag->id);

    // Should find the bookmark but not show pinned section
    assertBookmarkCardVisible($component, 'Pinned Tagged');
    $component->assertDontSee('Festede');
});

test('can reorder pinned bookmarks', function () {
    $bookmark1 = Bookmark::factory()->pinned(0)->create(['title' => 'First']);
    $bookmark2 = Bookmark::factory()->pinned(1)->create(['title' => 'Second']);
    $bookmark3 = Bookmark::factory()->pinned(2)->create(['title' => 'Third']);

    // Move bookmark3 to position 0 (first)
    Livewire::test(Index::class)
        ->call('updatePinnedOrder', "pinned-{$bookmark3->id}", 0);

    expect($bookmark3->fresh()->pinned_order)->toBe(0);
    expect($bookmark1->fresh()->pinned_order)->toBe(1);
    expect($bookmark2->fresh()->pinned_order)->toBe(2);
});

test('pinned bookmarks from any folder shown on main view', function () {
    $folder = BookmarkFolder::factory()->create(['name' => 'Test Folder']);
    $pinnedStandalone = Bookmark::factory()->pinned(0)->create(['title' => 'Pinned Standalone']);
    $pinnedInFolder = Bookmark::factory()->pinned(1)->inFolder($folder->id)->create(['title' => 'Pinned In Folder']);

    $component = Livewire::test(Index::class);

    // Both pinned bookmarks should be visible in pinned section
    assertBookmarkCardVisible($component, 'Pinned Standalone');
    assertBookmarkCardVisible($component, 'Pinned In Folder');
    $component->assertSee('Festede');
    // The folder indicator should be visible for the pinned bookmark in folder
    $component->assertSee('Test Folder');
});

test('pinned bookmarks are returned in correct order', function () {
    $bookmark3 = Bookmark::factory()->pinned(2)->create(['title' => 'Third']);
    $bookmark1 = Bookmark::factory()->pinned(0)->create(['title' => 'First']);
    $bookmark2 = Bookmark::factory()->pinned(1)->create(['title' => 'Second']);

    // Verify order using the model scope
    $pinnedBookmarks = Bookmark::pinned()->get();

    expect($pinnedBookmarks)->toHaveCount(3);
    expect($pinnedBookmarks[0]->title)->toBe('First');
    expect($pinnedBookmarks[1]->title)->toBe('Second');
    expect($pinnedBookmarks[2]->title)->toBe('Third');
});
