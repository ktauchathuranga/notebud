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

        $disk = Storage::disk('uploads');

        return response()->streamDownload(
            function () use ($disk, $file): void {
                $stream = $disk->readStream($file->path);

                if ($stream === false) {
                    abort(404);
                }

                fpassthru($stream);
                fclose($stream);
            },
            $file->original_name,
            ['Content-Disposition' => 'attachment; filename="'.$file->original_name.'"']
        );
    }
}
