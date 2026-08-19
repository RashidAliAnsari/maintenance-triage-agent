<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AgentDecisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentDecision extends Model
{
    /** @use HasFactory<AgentDecisionFactory> */
    use HasFactory;

    protected $fillable = [
        'maintenance_request_id',
        'tool_calls',
        'reasoning',
        'confidence',
        'outcome',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tool_calls' => 'array',
            'confidence' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<MaintenanceRequest, $this>
     */
    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }
}
