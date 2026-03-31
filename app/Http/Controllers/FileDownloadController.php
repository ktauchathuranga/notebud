<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDownloadController extends Controller
{
    public function __invoke(File $file): StreamedResponse
    {
        Gate::authorize('view', $file);

        $disk = Storage::disk(config('filesystems.uploads'));

        $stream = $disk->readStream($file->path);

        abort_unless(is_resource($stream), 404);

        return response()->streamDownload(
            function () use ($stream): void {
                fpassthru($stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }
            },
            $file->original_name,
            [
                'Content-Type' => $file->mime_type,
                'Content-Length' => $file->size,
            ]
        );
    }
}
