<?php

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'sanctum');
});

test('index returns all reports', function () {
    Report::factory()->count(3)->create();

    $this->getJson('/api/reports')
        ->assertOk()
        ->assertJsonStructure(['reports', 'message'])
        ->assertJsonCount(3, 'reports');
});

test('store creates a report record', function () {
    $payload = [
        'title' => 'Test Inventory Report',
        'type' => 'Inventory',
        'module' => 'Inventory',
        'period_from' => '2026-01-01',
        'period_to' => '2026-03-31',
        'format' => 'PDF',
        'status' => 'Draft',
    ];

    $this->postJson('/api/reports', $payload)
        ->assertCreated()
        ->assertJsonPath('data.title', 'Test Inventory Report')
        ->assertJsonPath('data.generated_by', $this->user->name);

    $this->assertDatabaseHas('reports', ['title' => 'Test Inventory Report']);
});

test('store validates required fields', function () {
    $this->postJson('/api/reports', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'type', 'format', 'status']);
});

test('download returns 404 when no file generated', function () {
    $report = Report::factory()->create(['file_path' => null]);

    $this->getJson("/api/reports/{$report->id}/download")
        ->assertNotFound();
});

test('download streams file when file_path exists', function () {
    Storage::fake('local');

    $report = Report::factory()->create([
        'format' => 'PDF',
        'file_path' => 'reports/test.pdf',
    ]);

    Storage::put('reports/test.pdf', '%PDF-1.4 fake content');

    $this->get("/api/reports/{$report->id}/download")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('destroy deletes report record', function () {
    $report = Report::factory()->create();

    $this->deleteJson("/api/reports/{$report->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Report deleted successfully.');

    $this->assertDatabaseMissing('reports', ['id' => $report->id]);
});

test('destroy also removes file from storage', function () {
    Storage::fake('local');
    Storage::put('reports/test.pdf', 'content');

    $report = Report::factory()->create(['file_path' => 'reports/test.pdf']);

    $this->deleteJson("/api/reports/{$report->id}")->assertOk();

    Storage::assertMissing('reports/test.pdf');
});
