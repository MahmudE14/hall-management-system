<?php

use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->withoutVite();
});

test('guests download a published notice PDF with a generated safe filename', function () {
    $notice = Notice::factory()->published()->create([
        'attachment_path' => 'notices/private-generated.pdf',
    ]);
    Storage::disk('local')->put($notice->attachment_path, '%PDF-1.4 published notice');

    $response = $this->get(route('notices.attachment', $notice));

    $response->assertOk()
        ->assertDownload("notice-{$notice->id}.pdf")
        ->assertHeader('content-type', 'application/pdf');

    expect($response->streamedContent())->toBe('%PDF-1.4 published notice')
        ->and($response->headers->get('content-disposition'))->not->toContain('notices/')
        ->and($response->headers->get('content-disposition'))->not->toContain('private-generated');
});

test('the published detail exposes the attachment route URL', function () {
    $notice = Notice::factory()->published()->create([
        'attachment_path' => 'notices/private-generated.pdf',
    ]);
    Storage::disk('local')->put($notice->attachment_path, '%PDF-1.4 published notice');

    $this->get(route('notices.show', $notice))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('notice.has_attachment', true)
            ->where('notice.attachment_url', route('notices.attachment', $notice))
        );
});

test('a draft notice attachment request returns not found', function () {
    $notice = Notice::factory()->create([
        'attachment_path' => 'notices/private-generated.pdf',
    ]);
    Storage::disk('local')->put($notice->attachment_path, '%PDF-1.4 draft notice');

    $this->get(route('notices.attachment', $notice))->assertNotFound();
});

test('a published notice without an attachment returns not found', function () {
    $notice = Notice::factory()->published()->create(['attachment_path' => null]);

    $this->get(route('notices.attachment', $notice))->assertNotFound();
});

test('a published notice whose stored attachment is missing fails safely', function () {
    $notice = Notice::factory()->published()->create([
        'attachment_path' => 'notices/missing.pdf',
    ]);

    $this->get(route('notices.attachment', $notice))->assertNotFound();
});

test('an unknown notice attachment request returns not found', function () {
    $this->get(route('notices.attachment', 999999))->assertNotFound();
});
