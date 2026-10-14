<?php

namespace App\Models;

use App\Enums\VehicleOwnership;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use Carbon\CarbonImmutable;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $plate_number
 * @property string $name
 * @property VehicleType $type
 * @property VehicleOwnership $ownership
 * @property string|null $rental_company
 * @property int $location_id
 * @property int $odometer
 * @property VehicleStatus $status
 * @property CarbonImmutable|null $last_service_at
 * @property CarbonImmutable|null $next_service_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Location $location
 * @property-read Collection<int, Booking> $bookings
 */
#[Fillable([
    'plate_number',
    'name',
    'type',
    'ownership',
    'rental_company',
    'location_id',
    'odometer',
    'status',
    'last_service_at',
    'next_service_at',
])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'ownership' => VehicleOwnership::class,
            'status' => VehicleStatus::class,
            'odometer' => 'integer',
            'last_service_at' => 'date',
            'next_service_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
