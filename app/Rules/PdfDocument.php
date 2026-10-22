<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;

class PdfDocument implements ValidationRule
{
    private const int MAX_BYTES = 5 * 1024 * 1024;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The :attribute must be a valid uploaded file.');

            return;
        }

        $size = $value->getSize();

        if ($size === false || $size > self::MAX_BYTES) {
            $fail('The :attribute may not be greater than 5 MB.');

            return;
        }

        if (
            strtolower($value->getClientOriginalExtension()) !== 'pdf'
            || $value->getMimeType() !== 'application/pdf'
        ) {
            $fail('The :attribute must be a PDF file.');

            return;
        }

        $path = $value->getRealPath();

        if ($path === false) {
            $fail('The :attribute must be a genuine PDF file.');

            return;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            $fail('The :attribute must be a genuine PDF file.');

            return;
        }

        $signature = fread($handle, 5);
        fclose($handle);

        if ($signature !== '%PDF-') {
            $fail('The :attribute must be a genuine PDF file.');
        }
    }
}
