<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\Vector;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $client_id
 * @property int $document_id
 * @property int $chunk_index
 * @property string $content
 * @property int $page_start
 * @property int $page_end
 * @property Vector $embedding
 * @property string $embedding_model
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['chunk_index', 'content', 'page_start', 'page_end', 'embedding', 'embedding_model'])]
class DocumentChunk extends Model
{
    use BelongsToOrganization;

    protected function casts(): array
    {
        return [
            'embedding' => Vector::class,
        ];
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
