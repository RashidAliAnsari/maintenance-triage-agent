<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PolicyDocumentType;
use Database\Factories\PolicyDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolicyDocument extends Model
{
    /** @use HasFactory<PolicyDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'content',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PolicyDocumentType::class,
        ];
    }

    /**
     * @return HasMany<DocumentChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
    }
}
