<?php

use App\Models\Role;
use App\Models\SystemLabel;
use App\Models\User;

function makeUserWithRole(array $permissions = [], string $roleName = 'Field Technician'): User
{
    $role = Role::create([
        'name' => $roleName.' '.uniqid(),
        'description' => 'Test role',
        'permissions' => $permissions,
    ]);

    return User::factory()->create(['role_id' => $role->id]);
}

test('any authenticated user can read the effective labels map', function () {
    SystemLabel::create(['key' => 'common.button.save', 'group' => 'common', 'default_value' => 'Save']);
    SystemLabel::create(['key' => 'common.button.cancel', 'group' => 'common', 'value' => 'Never Mind', 'default_value' => 'Cancel']);

    $this->actingAs(makeUserWithRole(), 'sanctum');

    $response = $this->getJson('/api/system-labels')
        ->assertOk()
        ->assertJsonPath('status', 'success');

    expect($response->json('data'))->toMatchArray([
        'common.button.save' => 'Save',
        'common.button.cancel' => 'Never Mind',
    ]);
});

test('reading labels requires authentication', function () {
    $this->getJson('/api/system-labels')->assertUnauthorized();
});

test('a user without the configure-labels permission cannot bulk update', function () {
    SystemLabel::create(['key' => 'common.button.save', 'group' => 'common', 'default_value' => 'Save']);
    $this->actingAs(makeUserWithRole([]), 'sanctum');

    $this->putJson('/api/system-labels/bulk', [
        'labels' => [['key' => 'common.button.save', 'value' => 'Store']],
    ])->assertForbidden();
});

test('a user without the permission cannot view the manage list', function () {
    $this->actingAs(makeUserWithRole([]), 'sanctum');

    $this->getJson('/api/system-labels/manage')->assertForbidden();
});

test('a user with the configure-labels permission can bulk update', function () {
    $label = SystemLabel::create(['key' => 'common.button.save', 'group' => 'common', 'default_value' => 'Save']);
    $user = makeUserWithRole(['System Settings: Configure Global Settings']);
    $this->actingAs($user, 'sanctum');

    $this->putJson('/api/system-labels/bulk', [
        'labels' => [['key' => 'common.button.save', 'value' => 'Store']],
    ])->assertOk()->assertJsonPath('status', 'success');

    $label->refresh();
    expect($label->value)->toBe('Store');
    expect($label->updated_by)->toBe($user->id);
});

test('an admin role bypasses the permission check', function () {
    SystemLabel::create(['key' => 'common.button.save', 'group' => 'common', 'default_value' => 'Save']);
    $user = makeUserWithRole([], 'System Administrator');

    $this->actingAs($user, 'sanctum');

    $this->putJson('/api/system-labels/bulk', [
        'labels' => [['key' => 'common.button.save', 'value' => 'Store']],
    ])->assertOk();
});

test('reset restores a label to its default value', function () {
    $label = SystemLabel::create([
        'key' => 'common.button.save',
        'group' => 'common',
        'value' => 'Store',
        'default_value' => 'Save',
    ]);
    $user = makeUserWithRole(['System Settings: Configure Global Settings']);
    $this->actingAs($user, 'sanctum');

    $this->patchJson("/api/system-labels/{$label->id}/reset")
        ->assertOk()
        ->assertJsonPath('status', 'success');

    $label->refresh();
    expect($label->value)->toBeNull();
    expect($label->effectiveValue())->toBe('Save');
});

test('bulk update rejects an unknown label key', function () {
    $user = makeUserWithRole(['System Settings: Configure Global Settings']);
    $this->actingAs($user, 'sanctum');

    $this->putJson('/api/system-labels/bulk', [
        'labels' => [['key' => 'does.not.exist', 'value' => 'X']],
    ])->assertStatus(422);
});

test('bulk update rejects an oversized value', function () {
    SystemLabel::create(['key' => 'common.button.save', 'group' => 'common', 'default_value' => 'Save']);
    $user = makeUserWithRole(['System Settings: Configure Global Settings']);
    $this->actingAs($user, 'sanctum');

    $this->putJson('/api/system-labels/bulk', [
        'labels' => [['key' => 'common.button.save', 'value' => str_repeat('a', 2001)]],
    ])->assertStatus(422);
});
