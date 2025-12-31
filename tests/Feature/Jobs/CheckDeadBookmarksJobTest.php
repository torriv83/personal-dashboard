<?php

declare(strict_types=1);

use App\Jobs\CheckDeadBookmarksJob;
use App\Models\Bookmark;
use App\Models\BookmarkTag;
use App\Notifications\DeadLinksChecked;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create();
    $this->actingAs($this->user);
});

test('adds dead tag when bookmark becomes dead', function () {
    $bookmark = Bookmark::factory()->create(['is_dead' => false]);

    // Mock HTTP to return 404
    Http::fake([
        '*' => Http::response(null, 404),
    ]);

    (new CheckDeadBookmarksJob($bookmark->id))->handle();

    $bookmark->refresh();

    expect($bookmark->is_dead)->toBeTrue();
    expect($bookmark->tags()->where('name', 'Død')->exists())->toBeTrue();
});

test('removes dead tag when bookmark is no longer dead', function () {
    // Create dead tag first
    $deadTag = BookmarkTag::factory()->create(['name' => 'Død', 'color' => 'red']);

    $bookmark = Bookmark::factory()->create(['is_dead' => true]);
    $bookmark->tags()->attach($deadTag);

    // Mock HTTP to return 200
    Http::fake([
        '*' => Http::response(null, 200),
    ]);

    (new CheckDeadBookmarksJob($bookmark->id))->handle();

    $bookmark->refresh();

    expect($bookmark->is_dead)->toBeFalse();
    expect($bookmark->tags()->where('name', 'Død')->exists())->toBeFalse();
});

test('creates dead tag if it does not exist', function () {
    expect(BookmarkTag::where('name', 'Død')->exists())->toBeFalse();

    $bookmark = Bookmark::factory()->create(['is_dead' => false]);

    Http::fake([
        '*' => Http::response(null, 404),
    ]);

    (new CheckDeadBookmarksJob($bookmark->id))->handle();

    $bookmark->refresh();

    expect(BookmarkTag::where('name', 'Død')->exists())->toBeTrue();
    expect($bookmark->tags()->where('name', 'Død')->exists())->toBeTrue();
});

test('adds dead tag on connection error', function () {
    $bookmark = Bookmark::factory()->create(['is_dead' => false]);

    // Mock HTTP to throw connection exception
    Http::fake([
        '*' => function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        },
    ]);

    (new CheckDeadBookmarksJob($bookmark->id))->handle();

    $bookmark->refresh();

    expect($bookmark->is_dead)->toBeTrue();
    expect($bookmark->tags()->where('name', 'Død')->exists())->toBeTrue();
});

test('does not duplicate dead tag when already attached', function () {
    $deadTag = BookmarkTag::factory()->create(['name' => 'Død', 'color' => 'red']);

    $bookmark = Bookmark::factory()->create(['is_dead' => false]);
    $bookmark->tags()->attach($deadTag);

    Http::fake([
        '*' => Http::response(null, 404),
    ]);

    (new CheckDeadBookmarksJob($bookmark->id))->handle();

    $bookmark->refresh();

    // Should still only have one "Død" tag
    expect($bookmark->tags()->where('name', 'Død')->count())->toBe(1);
});

test('sends notification when checking all bookmarks', function () {
    Notification::fake();

    Bookmark::factory()->count(3)->create(['is_dead' => false]);

    Http::fake([
        '*' => Http::response(null, 200),
    ]);

    (new CheckDeadBookmarksJob)->handle();

    Notification::assertSentTo(
        $this->user,
        DeadLinksChecked::class,
        function ($notification) {
            return $notification->totalChecked === 3
                && $notification->deadFound === 0
                && $notification->newlyDead === 0
                && $notification->revived === 0;
        }
    );
});

test('notification includes newly dead count', function () {
    Notification::fake();

    Bookmark::factory()->count(2)->create(['is_dead' => false]);

    Http::fake([
        '*' => Http::response(null, 404),
    ]);

    (new CheckDeadBookmarksJob)->handle();

    Notification::assertSentTo(
        $this->user,
        DeadLinksChecked::class,
        function ($notification) {
            return $notification->totalChecked === 2
                && $notification->deadFound === 2
                && $notification->newlyDead === 2;
        }
    );
});

test('notification includes revived count', function () {
    Notification::fake();

    Bookmark::factory()->count(2)->create(['is_dead' => true]);

    Http::fake([
        '*' => Http::response(null, 200),
    ]);

    (new CheckDeadBookmarksJob)->handle();

    Notification::assertSentTo(
        $this->user,
        DeadLinksChecked::class,
        function ($notification) {
            return $notification->totalChecked === 2
                && $notification->deadFound === 0
                && $notification->revived === 2;
        }
    );
});

test('does not send notification for single bookmark check', function () {
    Notification::fake();

    $bookmark = Bookmark::factory()->create(['is_dead' => false]);

    Http::fake([
        '*' => Http::response(null, 404),
    ]);

    (new CheckDeadBookmarksJob($bookmark->id))->handle();

    Notification::assertNothingSent();
});
