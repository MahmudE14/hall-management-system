<?php

use App\Enums\NoticeCategory;
use App\Enums\NoticeStatus;
use App\Models\Notice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('staff roles can create notices in every category and status', function (string $role) {
    $user = noticeCreationUser($role);

    foreach (NoticeCategory::cases() as $index => $category) {
        $status = $index % 2 === 0 ? NoticeStatus::Published : NoticeStatus::Draft;

        $this->actingAs($user)
            ->post(route('admin.notices.store'), [
                'title' => "{$role} {$category->value}",
                'body' => 'An official notice body.',
                'category' => $category->value,
                'status' => $status->value,
                'is_pinned' => false,
            ])
            ->assertRedirect(route('admin.notices.index'));
    }

    $notices = Notice::query()->get()->keyBy(fn (Notice $notice): string => $notice->category->value);

    expect($notices)->toHaveCount(3)
        ->and($notices[NoticeCategory::ProvostOffice->value]->status)->toBe(NoticeStatus::Published)
        ->and($notices[NoticeCategory::ProvostOffice->value]->published_at)->not->toBeNull()
        ->and($notices[NoticeCategory::HallOffice->value]->status)->toBe(NoticeStatus::Draft)
        ->and($notices[NoticeCategory::HallOffice->value]->published_at)->toBeNull()
        ->and($notices[NoticeCategory::Club->value]->status)->toBe(NoticeStatus::Published)
        ->and($notices[NoticeCategory::Club->value]->published_at)->not->toBeNull();
})->with(['super_admin', 'provost', 'hall_office_staff']);

test('a club admin can create pinned club notices as draft or published', function (NoticeStatus $status) {
    $clubAdmin = noticeCreationUser('club_admin');

    $this->actingAs($clubAdmin)
        ->post(route('admin.notices.store'), [
            'title' => 'Club announcement',
            'body' => 'The club will meet on Friday.',
            'category' => NoticeCategory::Club->value,
            'status' => $status->value,
            'is_pinned' => true,
        ])
        ->assertRedirect(route('admin.notices.index'));

    $notice = Notice::query()->sole();

    expect($notice->created_by)->toBe($clubAdmin->id)
        ->and($notice->category)->toBe(NoticeCategory::Club)
        ->and($notice->status)->toBe($status)
        ->and($notice->is_pinned)->toBeTrue()
        ->and($notice->published_at === null)->toBe($status === NoticeStatus::Draft);
})->with([
    'draft' => NoticeStatus::Draft,
    'published' => NoticeStatus::Published,
]);

test('a club admin without a student profile can create a club notice', function () {
    $clubAdmin = noticeCreationUser('club_admin');

    expect($clubAdmin->studentProfile)->toBeNull();

    $this->actingAs($clubAdmin)
        ->post(route('admin.notices.store'), [
            'title' => 'Club notice',
            'body' => 'A club notice without a student profile.',
            'category' => NoticeCategory::Club->value,
            'status' => NoticeStatus::Published->value,
        ])
        ->assertRedirect(route('admin.notices.index'));

    expect(Notice::query()->count())->toBe(1);
});

test('a club admin cannot create official-category notices', function (NoticeCategory $category) {
    $clubAdmin = noticeCreationUser('club_admin');

    $this->actingAs($clubAdmin)
        ->post(route('admin.notices.store'), [
            'title' => 'Official notice',
            'body' => 'This category is outside Club Admin authority.',
            'category' => $category->value,
            'status' => NoticeStatus::Draft->value,
        ])
        ->assertForbidden();

    expect(Notice::query()->count())->toBe(0);
})->with([
    'provost office' => NoticeCategory::ProvostOffice,
    'hall office' => NoticeCategory::HallOffice,
]);

test('roles without notice authority cannot create notices', function (string $role) {
    $this->actingAs(noticeCreationUser($role))
        ->post(route('admin.notices.store'), [
            'title' => 'Unauthorized notice',
            'body' => 'This request must be refused.',
            'category' => NoticeCategory::Club->value,
            'status' => NoticeStatus::Draft->value,
        ])
        ->assertForbidden();

    expect(Notice::query()->count())->toBe(0);
})->with(['house_tutor', 'student']);

test('guests are redirected to sign in before creating notices', function () {
    $this->post(route('admin.notices.store'), [
        'title' => 'Guest notice',
        'body' => 'This request must be refused.',
        'category' => NoticeCategory::Club->value,
        'status' => NoticeStatus::Draft->value,
    ])->assertRedirect(route('login'));

    expect(Notice::query()->count())->toBe(0);
});

test('an inactive club admin is signed out before creating notices', function () {
    $clubAdmin = noticeCreationUser('club_admin', active: false);

    $this->actingAs($clubAdmin)
        ->post(route('admin.notices.store'), [
            'title' => 'Inactive club admin',
            'body' => 'This request must be refused.',
            'category' => NoticeCategory::Club->value,
            'status' => NoticeStatus::Draft->value,
        ])
        ->assertRedirect(route('login'));

    expect(Notice::query()->count())->toBe(0);
});

test('notice creation validates required fields', function () {
    $user = noticeCreationUser('hall_office_staff');

    $this->actingAs($user)
        ->post(route('admin.notices.store'), [])
        ->assertSessionHasErrors(['title', 'body', 'category', 'status']);

    expect(Notice::query()->count())->toBe(0);
});

test('a published notice is created with a success toast', function () {
    $user = noticeCreationUser('provost');

    $this->actingAs($user)
        ->post(route('admin.notices.store'), [
            'title' => 'Convocation notice',
            'body' => 'The convocation will be held next week.',
            'category' => NoticeCategory::ProvostOffice->value,
            'status' => NoticeStatus::Published->value,
        ])
        ->assertRedirect(route('admin.notices.index'))
        ->assertInertiaFlash('toast');
});

test('a valid PDF is stored privately under a generated filename', function () {
    Storage::fake('local');
    $user = noticeCreationUser('hall_office_staff');

    $this->actingAs($user)
        ->post(route('admin.notices.store'), [
            'title' => 'Notice with attachment',
            'body' => 'The attachment must be stored safely.',
            'category' => NoticeCategory::HallOffice->value,
            'status' => NoticeStatus::Published->value,
            'attachment' => noticeCreationPdfUpload('original-user-filename.pdf'),
        ])
        ->assertRedirect(route('admin.notices.index'));

    $notice = Notice::query()->sole();

    expect($notice->attachment_path)->not->toBeNull()
        ->and($notice->attachment_path)->toStartWith('notices/')
        ->and($notice->attachment_path)->toEndWith('.pdf')
        ->and($notice->attachment_path)->not->toContain('original-user-filename')
        ->and(Storage::disk('local')->exists($notice->attachment_path))->toBeTrue();
});

test('invalid attachments are rejected without creating a notice or storing a file', function (UploadedFile $attachment) {
    Storage::fake('local');
    $user = noticeCreationUser('hall_office_staff');

    $this->actingAs($user)
        ->post(route('admin.notices.store'), [
            'title' => 'Invalid attachment',
            'body' => 'The request must be rejected.',
            'category' => NoticeCategory::HallOffice->value,
            'status' => NoticeStatus::Draft->value,
            'attachment' => $attachment,
        ])
        ->assertSessionHasErrors('attachment');

    expect(Notice::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'non-PDF' => fn () => UploadedFile::fake()
        ->createWithContent('notice.pdf', 'Not a PDF.')
        ->mimeType('text/plain'),
    'renamed PHP' => fn () => UploadedFile::fake()
        ->createWithContent('notice.pdf', '<?php echo "unsafe"; ?>')
        ->mimeType('application/x-php'),
    'oversized PDF' => fn () => noticeCreationPdfUpload('large.pdf', 5121),
]);

function noticeCreationUser(string $role, bool $active = true): User
{
    $user = User::factory()->create(['is_active' => $active]);
    $user->roles()->attach(Role::query()->firstOrCreate(['name' => $role]));

    return $user;
}

function noticeCreationPdfUpload(string $name = 'notice.pdf', int $sizeInKb = 1): UploadedFile
{
    $content = "%PDF-1.4\n";
    $content .= str_repeat('A', max(0, ($sizeInKb * 1024) - strlen($content)));

    return UploadedFile::fake()
        ->createWithContent($name, $content)
        ->mimeType('application/pdf');
}
