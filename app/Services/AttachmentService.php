<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Validated private-storage uploads. Files are stored on the `private`
 * disk and served only through authorised download routes — never
 * directly from the public web root.
 */
class AttachmentService
{
    public function store(UploadedFile $file, $attachable, int $tenantId, ?int $userId, string $visibility = 'public'): Attachment
    {
        $maxKb = config('supportflow.upload.max_kb', 10240);
        abort_if($file->getSize() > $maxKb * 1024, 422, 'File exceeds the maximum size of '.($maxKb / 1024).' MB.');

        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = config('supportflow.upload.allowed_mimes', []);
        abort_unless(in_array($extension, $allowed, true), 422, 'File type .'.$extension.' is not allowed.');

        // MIME sniff check (blocks disguised executables).
        $mime = $file->getMimeType();
        abort_if(str_contains((string) $mime, 'x-msdownload') || in_array($extension, ['php', 'phtml', 'exe', 'sh', 'bat'], true), 422, 'File type not allowed.');

        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $safeName = substr($safeName ?: 'file', 0, 80).'.'.$extension;
        $path = "tenant-{$tenantId}/".date('Y/m').'/'.Str::random(24).'-'.$safeName;

        Storage::disk('private')->putFileAs(dirname($path), $file, basename($path));

        return Attachment::create([
            'tenant_id' => $tenantId,
            'attachable_type' => get_class($attachable),
            'attachable_id' => $attachable->id,
            'uploaded_by' => $userId,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $mime,
            'size_bytes' => $file->getSize(),
            'visibility' => $visibility,
        ]);
    }
}
