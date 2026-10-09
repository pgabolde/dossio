<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Jobs\ProcessDocument;
use App\Models\Client;
use App\Models\Document;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function store(StoreDocumentRequest $request, Client $client): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');

        $path = $file->store('documents/' . $client->organization_id, 'local');

        if ($path === false) {
            throw new RuntimeException('Échec de l\'enregistrement du fichier.');
        }

        $document = $client->documents()->create([
            'original_filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        ProcessDocument::dispatch($document);

        return back();
    }

    public function download(Document $document): StreamedResponse
    {
        Gate::authorize('view', $document->client);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->download($document->path, $document->original_filename);
    }
}
