<?php

namespace App\Models;

use App\Enums\LocationType;
use Carbon\CarbonImmutable;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $region_id
 * @property string $name
 * @property LocationType $type
 * @property string|null $address
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Region $region
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Driver> $drivers
 * @property-read Collection<int, Vehicle> $vehicles
 */
#[Fillable(['region_id', 'name', 'type', 'address'])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LocationType::class,
        ];
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Driver, $this>
     */
    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    /**
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
