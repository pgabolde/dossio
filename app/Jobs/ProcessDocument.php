<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Support\CurrentOrganization;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

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

        // TODO B6/B7 : appel au service Python
    }

    public function failed(Throwable $exception): void
    {
        $this->document->update(['status' => DocumentStatus::Failed, 'error_message' => $exception->getMessage()]);
    }
}
