<?php

use App\Actions\Notices\CreateNotice;
use App\Actions\Notices\StoreNoticeAttachment;
use App\Enums\NoticeCategory;
use App\Enums\NoticeStatus;
use App\Models\Notice;
use App\Models\User;
use App\Rules\PdfDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

test('the PDF rule accepts a genuine PDF at exactly the 5 MB limit', function () {
    $validator = Validator::make(
        ['attachment' => pdfUpload(5120)],
        ['attachment' => [new PdfDocument]],
    );

    expect($validator->passes())->toBeTrue();
});

test('the PDF rule rejects a PDF larger than 5 MB', function () {
    $validator = Validator::make(
        ['attachment' => pdfUpload(5121)],
        ['attachment' => [new PdfDocument]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('attachment'))->toBeTrue();
});

test('the PDF rule rejects non-PDF, renamed executable, and renamed PHP uploads', function (UploadedFile $attachment) {
    $validator = Validator::make(
        ['attachment' => $attachment],
        ['attachment' => [new PdfDocument]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('attachment'))->toBeTrue();
})->with([
    'plain text renamed to PDF' => fn () => UploadedFile::fake()
        ->createWithContent('notes.pdf', 'This is not a PDF.')
        ->mimeType('text/plain'),
    'executable renamed to PDF' => fn () => UploadedFile::fake()
        ->createWithContent('setup.pdf', 'MZ executable payload')
        ->mimeType('application/octet-stream'),
    'PHP renamed to PDF' => fn () => UploadedFile::fake()
        ->createWithContent('payload.pdf', '<?php echo "unsafe"; ?>')
        ->mimeType('application/x-php'),
    'PDF content with non-PDF MIME type' => fn () => pdfUpload(1)->mimeType('image/png'),
    'PDF content with non-PDF filename' => fn () => pdfUpload(1, 'notice.txt'),
]);

test('accepted attachments are stored privately under a generated filename', function () {
    Storage::fake('local');

    $path = app(StoreNoticeAttachment::class)->store(pdfUpload(1, 'original-user-name.pdf'));

    expect($path)->toStartWith('notices/')
        ->and($path)->toEndWith('.pdf')
        ->and($path)->not->toContain('original-user-name')
        ->and(Storage::disk('local')->exists($path))->toBeTrue();
});

test('a stored attachment can be deleted', function () {
    Storage::fake('local');
    $attachments = app(StoreNoticeAttachment::class);
    $path = $attachments->store(pdfUpload(1));

    $attachments->delete($path);

    expect(Storage::disk('local')->exists($path))->toBeFalse();
});

test('a failed notice creation removes the stored attachment', function () {
    Storage::fake('local');
    $creator = User::factory()->create();

    Notice::creating(function (): void {
        throw new RuntimeException('Forced notice failure.');
    });

    expect(fn () => app(CreateNotice::class)->handle(
        $creator,
        [
            'title' => 'Attachment cleanup',
            'body' => 'This notice creation is forced to fail.',
            'category' => NoticeCategory::HallOffice,
            'status' => NoticeStatus::Published,
            'is_pinned' => false,
        ],
        pdfUpload(1),
    ))->toThrow(RuntimeException::class, 'Forced notice failure.');

    expect(Notice::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

function pdfUpload(int $sizeInKb, string $name = 'notice.pdf', string $mimeType = 'application/pdf'): UploadedFile
{
    $content = "%PDF-1.4\n";
    $content .= str_repeat('A', max(0, ($sizeInKb * 1024) - strlen($content)));

    return UploadedFile::fake()
        ->createWithContent($name, $content)
        ->mimeType($mimeType);
}
