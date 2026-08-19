<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RequestCategory;
use App\Enums\RequestStatus;
use App\Enums\RequestUrgency;
use App\Enums\Responsibility;
use Database\Factories\MaintenanceRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $unit_id
 * @property int $tenant_id
 * @property string $description
 * @property RequestCategory|null $category
 * @property RequestUrgency|null $urgency
 * @property RequestStatus $status
 * @property Responsibility|null $responsibility
 * @property string|null $estimated_hours
 * @property string|null $estimated_cost
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MaintenanceRequest extends Model
{
    /** @use HasFactory<MaintenanceRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'tenant_id',
        'description',
        'category',
        'urgency',
        'status',
        'responsibility',
        'estimated_hours',
        'estimated_cost',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => RequestCategory::class,
            'urgency' => RequestUrgency::class,
            'status' => RequestStatus::class,
            'responsibility' => Responsibility::class,
            'estimated_hours' => 'decimal:2',
            'estimated_cost' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return HasOne<WorkOrder, $this>
     */
    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class);
    }

    /**
     * @return HasMany<AgentDecision, $this>
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(AgentDecision::class);
    }
}
