<?php

use App\Models\User;

test('disk usage returns size, used, avail, and use percent', function () {
    $this->actingAs(User::factory()->create(), 'sanctum');

    $this->getJson('/api/system/disk-usage')
        ->assertOk()
        ->assertJsonStructure([
            'status',
            'data' => ['size', 'used', 'avail', 'use_percent'],
        ])
        ->assertJsonPath('status', 'success');
});

test('disk usage requires authentication', function () {
    $this->getJson('/api/system/disk-usage')
        ->assertUnauthorized();
});
