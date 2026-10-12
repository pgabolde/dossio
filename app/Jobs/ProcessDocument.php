<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Support\CurrentOrganization;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Pgvector\Laravel\Vector;
use Throwable;

class ProcessDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    public int $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(public Document $document)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CurrentOrganization $currentOrganization): void
    {
        $currentOrganization->set($this->document->organization);

        $this->document->update(['status' => DocumentStatus::Processing]);

        $response = Http::timeout(Config::integer('services.ai_service.timeout'))
            ->connectTimeout(5)
            ->attach(
                'file',
                Storage::disk('local')->get($this->document->path),
                $this->document->original_filename
            )
            ->post(Config::string('services.ai_service.url').'/ingest');

        // On coupe si erreur 422 (erreur du PDF)
        if ($response->status() === 422) {
            $detail = $response->json('detail');
            $this->fail(is_string($detail) ? $detail : 'Document rejected by the AI service');

            return;
        }

        // On coupe toute autre erreur pour relancer le job plus tard
        $response->throw();

        /** @var array{embedding_model: string, chunks: list<array{text: string, page_start: int, page_end: int, embedding: list<float>}>} $payload */
        $payload = $response->json();

        DB::transaction(function () use ($payload) {
            $this->document->chunks()->delete();

            foreach ($payload['chunks'] as $index => $chunkData) {
                $chunk = new DocumentChunk([
                    'chunk_index' => $index,
                    'content' => $chunkData['text'],
                    'page_start' => $chunkData['page_start'],
                    'page_end' => $chunkData['page_end'],
                    'embedding' => new Vector($chunkData['embedding']),
                    'embedding_model' => $payload['embedding_model'],
                ]);

                $chunk->client_id = $this->document->client_id;

                $this->document->chunks()->save($chunk);
            }

            $this->document->update([
                'status' => DocumentStatus::Ready,
                'error_message' => null,
            ]);
        });
    }

    public function failed(Throwable $exception): void
    {
        $this->document->update(['status' => DocumentStatus::Failed, 'error_message' => $exception->getMessage()]);
    }
}
