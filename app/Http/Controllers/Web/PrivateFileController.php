<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Batches;
use App\Models\ScholarUploadedFiles;
use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class PrivateFileController extends Controller
{
    public function payroll(Request $request, string $batch, string $file)
    {
        $batch = Batches::findOrFail(Hashids::decode($batch)[0] ?? 0);
        $access = app(SystemPermissions::class);
        if ($access->shouldScopeToRegion($request->user())) {
            abort_unless($access->agencyNameFor($request->user()) && filled($batch->region), 403);
        }
        $permissions = $access->payrollBatchPermissions(
            $request->user(), $batch, $batch->status ?: 'draft'
        );
        abort_unless($permissions['canView'], 403);

        if ($file === 'excel') {
            abort_unless($permissions['canViewGeneratedExcel'], 403);

            return $this->send($batch->generated_excel_path, $batch->generated_excel_name ?: 'payroll.xlsx', false);
        }

        abort_unless(ctype_digit($file), 404);
        $attachment = $batch->logs()->whereKey($file)->firstOrFail();

        return $this->send($attachment->payroll_file_path, $attachment->payroll_file_name ?: 'payroll.pdf', true);
    }

    public function scholarImport(Request $request, string $file)
    {
        $permissions = app(SystemPermissions::class);
        abort_unless($permissions->can($request->user(), 'scholars.review'), 403);
        $file = ScholarUploadedFiles::findOrFail(Hashids::decode($file)[0] ?? 0);
        if ($permissions->shouldScopeToRegion($request->user())) {
            $agency = $permissions->agencyNameFor($request->user());
            abort_unless($agency && strcasecmp($agency, $file->region_office ?? '') === 0, 403);
        }

        return $this->send($file->filepath, $file->filename ?: 'scholar-import.xlsx', false);
    }

    private function send(?string $path, string $name, bool $inline)
    {
        abort_unless($path && Storage::disk('sensitive')->exists($path), 404);
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];

        return Storage::disk('sensitive')->response($path, $name, $headers, $inline ? 'inline' : 'attachment');
    }
}
