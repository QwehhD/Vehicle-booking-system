<?php

namespace App\Models;

use App\Support\Like;
use Carbon\CarbonImmutable;
use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string $description
 * @property array<string, mixed>|null $properties
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $user
 * @property-read Model|null $subject
 */
#[Fillable([
    'user_id',
    'action',
    'subject_type',
    'subject_id',
    'description',
    'properties',
    'ip_address',
    'user_agent',
])]
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Filter log aktivitas; kunci yang kosong diabaikan.
     *
     * @param  Builder<self>  $query
     * @param  array{search?: string|null, user_id?: int|null, action?: string|null, date_from?: string|null, date_to?: string|null}  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query
                ->where(fn (Builder $query) => $query
                    ->whereRaw(Like::clause('description'), [Like::contains($search)])
                    ->orWhereRaw(Like::clause('ip_address'), [Like::contains($search)])))
            ->when($filters['user_id'] ?? null, fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->when($filters['action'] ?? null, fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $from) => $query
                ->where('created_at', '>=', CarbonImmutable::parse($from)->startOfDay()))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $to) => $query
                ->where('created_at', '<', CarbonImmutable::parse($to)->addDay()->startOfDay()));
    }
}
