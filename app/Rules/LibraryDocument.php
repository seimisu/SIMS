<?php

namespace App\Rules;

use Closure;
use DOMDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use Throwable;
use ZipArchive;

class LibraryDocument implements ValidationRule
{
    private const TYPES = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/x-ole-storage', 'application/cdfv2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/cdfv2'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'csv' => ['text/plain', 'text/csv', 'application/csv', 'text/x-csv'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The document could not be uploaded. Please select it again.');

            return;
        }
        if ($value->getSize() === 0) {
            $fail('This file is empty. Please upload a document with content.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        if (! isset(self::TYPES[$extension])) {
            $fail('Please upload a PDF, DOC, DOCX, XLS, XLSX, CSV, JPG, JPEG, or PNG file.');

            return;
        }
        if (! in_array(strtolower($value->getMimeType() ?? ''), self::TYPES[$extension], true)) {
            $fail('The file contents do not match its file type. Please upload a valid document.');

            return;
        }

        try {
            $path = $value->getRealPath();
            $contents = file_get_contents($path);
            $readable = match ($extension) {
                'pdf' => $this->readablePdf($contents),
                'docx' => $this->readableOfficeArchive($path, 'word/document.xml', 'document'),
                'xlsx' => $this->readableOfficeArchive($path, 'xl/workbook.xml', 'workbook'),
                'xls' => (new Xls)->canRead($path),
                'doc' => str_starts_with($contents, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")
                    && strlen($contents) >= 512 && str_contains($contents, "W\0o\0r\0d\0D\0o\0c\0u\0m\0e\0n\0t\0"),
                'csv' => ! str_contains($contents, "\0") && trim($contents, "\xEF\xBB\xBF\x20\t\r\n") !== '',
                'jpg', 'jpeg' => @getimagesize($path) !== false && str_ends_with(rtrim($contents, "\r\n"), "\xFF\xD9"),
                'png' => @getimagesize($path) !== false && str_ends_with($contents, "\0\0\0\0IEND\xAE\x42\x60\x82"),
            };
        } catch (Throwable $exception) {
            $readable = false;
        }

        if (! $readable) {
            $fail('This document appears damaged, unreadable, or password-protected. Please upload a valid copy.');
        }
    }

    private function readablePdf(string $contents): bool
    {
        if (preg_match('/\A%PDF-[12]\.[0-9]/', $contents) !== 1
            || preg_match('/startxref\s+(\d+)\s+%%EOF\s*\z/', substr($contents, -2048), $matches) !== 1) {
            return false;
        }
        $offset = (int) $matches[1];
        if ($offset <= 0 || $offset >= strlen($contents)) {
            return false;
        }

        // PDFs can use a traditional xref table or an xref stream object.
        return preg_match('/\A(?:xref\b|[0-9]+\s+[0-9]+\s+obj\b)/', substr($contents, $offset, 64)) === 1;
    }

    private function readableOfficeArchive(string $path, string $mainFile, string $rootName): bool
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            return false;
        }
        try {
            // Bound expansion and XML parsing during the upload request.
            if ($zip->numFiles > 2000) {
                return false;
            }
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $total += $stat['size'];
                if ($total > 100 * 1024 * 1024) {
                    return false;
                }
            }
            foreach (['[Content_Types].xml' => 'Types', $mainFile => $rootName] as $entry => $root) {
                $stat = $zip->statName($entry);
                if (! $stat || $stat['size'] > 16 * 1024 * 1024) {
                    return false;
                }
                $xml = $zip->getFromName($entry);
                if ($xml === false || stripos($xml, '<!DOCTYPE') !== false || crc32($xml) !== $stat['crc']) {
                    return false;
                }
                $previous = libxml_use_internal_errors(true);
                try {
                    $document = new DOMDocument;
                    if (! $document->loadXML($xml, LIBXML_NONET) || $document->documentElement?->localName !== $root) {
                        return false;
                    }
                } finally {
                    libxml_clear_errors();
                    libxml_use_internal_errors($previous);
                }
            }

            return true;
        } finally {
            $zip->close();
        }
    }
}
