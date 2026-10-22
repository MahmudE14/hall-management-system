<?php

use App\Actions\Complaints\GenerateComplaintTrackingNumber;
use App\Enums\ComplaintCategory;
use App\Enums\RegistrationStatus;
use App\Enums\ResidencyStatus;
use App\Models\Complaint;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('local');
});

test('a resident complaint stores a valid JPG on the private disk with a generated filename', function () {
    $user = imageUploadStudent();

    $this->actingAs($user)
        ->post(route('complaints.store'), [
            'category' => ComplaintCategory::Water->value,
            'title' => 'Leaking pipe',
            'description' => 'Water is leaking from the pipe under the sink.',
            'location' => 'Ignored for residents',
            'image' => UploadedFile::fake()->image('leaking-pipe.jpg', 800, 600)->size(1024),
        ])
        ->assertRedirect(route('dashboard'));

    $complaint = Complaint::query()->sole();

    expect($complaint->image_path)->not->toBeNull()
        ->and($complaint->image_path)->toStartWith('complaints/')
        ->and($complaint->image_path)->not->toContain('leaking-pipe')
        ->and(Storage::disk('local')->exists($complaint->image_path))->toBeTrue();
});

test('a PNG attachment is accepted and stored privately', function () {
    $user = imageUploadStudent();

    $this->actingAs($user)
        ->post(route('complaints.store'), [
            'category' => ComplaintCategory::Furniture->value,
            'title' => 'Broken chair',
            'description' => 'The chair in the study room has a broken leg.',
            'location' => 'Ignored for residents',
            'image' => UploadedFile::fake()->image('chair.png', 640, 480),
        ])
        ->assertRedirect(route('dashboard'));

    $complaint = Complaint::query()->sole();

    expect($complaint->image_path)->toStartWith('complaints/')
        ->and($complaint->image_path)->toEndWith('.png')
        ->and(Storage::disk('local')->exists($complaint->image_path))->toBeTrue();
});

test('an image at the 2 MB limit is accepted', function () {
    $user = imageUploadStudent();

    $this->actingAs($user)
        ->post(route('complaints.store'), [
            'category' => ComplaintCategory::Internet->value,
            'title' => 'Slow connection',
            'description' => 'The hall internet connection is very slow.',
            'location' => 'Ignored for residents',
            'image' => UploadedFile::fake()->image('limit.jpg', 200, 200)->size(2048),
        ])
        ->assertRedirect(route('dashboard'));

    expect(Complaint::query()->count())->toBe(1);
});

test('unsupported or executable uploads are rejected and leave no files', function (UploadedFile $image) {
    $user = imageUploadStudent();

    $this->actingAs($user)
        ->post(route('complaints.store'), [
            'category' => ComplaintCategory::Security->value,
            'title' => 'Suspicious file',
            'description' => 'This upload should not be accepted.',
            'location' => 'Ignored for residents',
            'image' => $image,
        ])
        ->assertSessionHasErrors('image');

    expect(Complaint::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'pdf document' => fn () => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
    'executable' => fn () => UploadedFile::fake()->create('setup.exe', 100, 'application/octet-stream'),
    'php renamed as jpg' => fn () => UploadedFile::fake()
        ->createWithContent('payload.jpg', '<?php echo "unsafe"; ?>')
        ->mimeType('application/x-php'),
    'gif image' => fn () => UploadedFile::fake()->image('animation.gif', 100, 100),
]);

test('an image larger than 2 MB is rejected and leaves no files', function () {
    $user = imageUploadStudent();

    $this->actingAs($user)
        ->post(route('complaints.store'), [
            'category' => ComplaintCategory::Cleanliness->value,
            'title' => 'Large photo',
            'description' => 'This image is too large for the complaint.',
            'location' => 'Ignored for residents',
            'image' => UploadedFile::fake()->image('too-large.jpg', 200, 200)->size(2049),
        ])
        ->assertSessionHasErrors('image');

    expect(Complaint::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('a failed complaint creation removes the stored image', function () {
    $user = imageUploadStudent();

    $this->mock(GenerateComplaintTrackingNumber::class, function (MockInterface $mock): void {
        $mock->shouldReceive('handle')->once()->andThrow(new RuntimeException('Tracking generation failed.'));
    });

    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($user)->post(route('complaints.store'), [
        'category' => ComplaintCategory::Water->value,
        'title' => 'Leaking pipe',
        'description' => 'Water is leaking from the pipe under the sink.',
        'location' => 'Ignored for residents',
        'image' => UploadedFile::fake()->image('leaking-pipe.jpg', 800, 600),
    ]))->toThrow(RuntimeException::class);

    expect(Complaint::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

function imageUploadStudent(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => 'student']));
    StudentProfile::factory()->for($user)->create([
        'registration_status' => RegistrationStatus::Approved,
        'residency_status' => ResidencyStatus::Resident,
    ]);

    return $user->fresh();
}
