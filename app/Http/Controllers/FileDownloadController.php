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

        return Storage::disk('uploads')->download($file->path, $file->original_name);
    }
}
