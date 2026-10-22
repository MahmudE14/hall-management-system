<?php

use App\Actions\Complaints\GenerateComplaintTrackingNumber;
use App\Models\Complaint;
use App\Models\ComplaintTrackingCounter;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('complaint tracking counters table contains the required columns', function () {
    expect(Schema::hasColumns('complaint_tracking_counters', [
        'id',
        'year',
        'last_number',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    $yearIndex = collect(Schema::getIndexes('complaint_tracking_counters'))
        ->firstWhere('name', 'complaint_tracking_counters_year_unique');

    expect($yearIndex)->not->toBeNull();
    expect($yearIndex['unique'])->toBeTrue();
});

test('the first complaint of a year receives sequence number one', function () {
    $trackingId = app(GenerateComplaintTrackingNumber::class)->handle(2026);

    expect($trackingId)->toBe('KJH-2026-0001');
    expect(ComplaintTrackingCounter::query()->where('year', 2026)->firstOrFail()->last_number)->toBe(1);
});

test('tracking numbers increment within a year', function () {
    $generator = app(GenerateComplaintTrackingNumber::class);

    expect($generator->handle(2026))->toBe('KJH-2026-0001');
    expect($generator->handle(2026))->toBe('KJH-2026-0002');
    expect($generator->handle(2026))->toBe('KJH-2026-0003');

    expect(ComplaintTrackingCounter::query()->where('year', 2026)->firstOrFail()->last_number)->toBe(3);
});

test('the tracking sequence restarts for each calendar year', function () {
    $generator = app(GenerateComplaintTrackingNumber::class);

    $generator->handle(2026);
    $generator->handle(2026);

    expect($generator->handle(2027))->toBe('KJH-2027-0001');
    expect($generator->handle(2027))->toBe('KJH-2027-0002');
    expect($generator->handle(2026))->toBe('KJH-2026-0003');

    expect(ComplaintTrackingCounter::query()->pluck('last_number', 'year')->all())
        ->toBe([2026 => 3, 2027 => 2]);
});

test('the generator defaults to the current calendar year', function () {
    $this->travelTo(now()->setDate(2031, 3, 14));

    expect(app(GenerateComplaintTrackingNumber::class)->handle())->toBe('KJH-2031-0001');
});

test('the database rejects duplicate complaint tracking identifiers', function () {
    Complaint::factory()->create(['tracking_id' => 'KJH-2026-0001']);

    expect(fn () => Complaint::factory()->create(['tracking_id' => 'KJH-2026-0001']))
        ->toThrow(QueryException::class);

    expect(Complaint::query()->where('tracking_id', 'KJH-2026-0001')->count())->toBe(1);
});
