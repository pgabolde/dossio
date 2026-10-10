<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Models\Concerns\BelongsToOrganization;
use Carbon\Carbon;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $client_id
 * @property string $original_filename
 * @property string $path
 * @property string $mime_type
 * @property int $size
 * @property DocumentStatus $status
 * @property string|null $error_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['original_filename', 'path', 'mime_type', 'size', 'status', 'error_message'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use BelongsToOrganization, HasFactory;

    protected $attributes = [
        'status' => DocumentStatus::Pending->value,
    ];

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return HasMany<DocumentChunk, $this> */
    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
    }

    protected function casts(): array
    {
        return ['status' => DocumentStatus::class];
    }
}
