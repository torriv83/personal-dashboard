<?php

declare(strict_types=1);

use App\Models\Assistant;

use function Pest\Laravel\get;

beforeEach(function () {
    $this->assistant = Assistant::factory()->create(['name' => 'Test Assistent']);
});

it('returns manifest.json with correct content', function () {
    $firstName = explode(' ', $this->assistant->name)[0];

    get(route('tasks.assistant.manifest', $this->assistant))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJson([
            'name' => $firstName . ' - Oppgaver',
            'short_name' => $firstName . ' - Oppgaver',
            'display' => 'standalone',
            'background_color' => '#1a1a1a',
            'theme_color' => '#1a1a1a',
        ])
        ->assertJsonPath('icons.0.src', '/icons/tasks-icon-192x192.png')
        ->assertJsonPath('icons.1.src', '/icons/tasks-icon-512x512.png');
});

it('returns manifest with correct start_url for assistant', function () {
    $response = get(route('tasks.assistant.manifest', $this->assistant))
        ->assertOk();

    $manifest = $response->json();

    expect($manifest['start_url'])->toContain($this->assistant->token);
    expect($manifest['scope'])->toBe('/oppgaver/' . $this->assistant->token);
});

it('returns service worker with correct content type', function () {
    get(route('tasks.assistant.sw', $this->assistant))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/javascript');
});

it('returns service worker with correct scope header', function () {
    $response = get(route('tasks.assistant.sw', $this->assistant))
        ->assertOk();

    $expectedScope = '/oppgaver/' . $this->assistant->token;
    expect($response->headers->get('Service-Worker-Allowed'))->toBe($expectedScope);
});

it('returns 404 for manifest with invalid token', function () {
    get('/oppgaver/invalid-token/manifest.json')
        ->assertNotFound();
});

it('returns 404 for service worker with invalid token', function () {
    get('/oppgaver/invalid-token/sw.js')
        ->assertNotFound();
});
