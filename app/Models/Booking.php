<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\BookingStatus;
use Carbon\CarbonImmutable;
use Database\Factories\BookingFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $code
 * @property string $requester_name
 * @property string $requester_department
 * @property string $purpose
 * @property string $destination
 * @property int $vehicle_id
 * @property int $driver_id
 * @property int $created_by
 * @property CarbonImmutable $start_at
 * @property CarbonImmutable $end_at
 * @property BookingStatus $status
 * @property int $current_level
 * @property int|null $odometer_start
 * @property int|null $odometer_end
 * @property string|null $fuel_used
 * @property CarbonImmutable|null $completed_at
 * @property string|null $cancel_reason
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Vehicle $vehicle
 * @property-read Driver $driver
 * @property-read User $creator
 * @property-read Collection<int, BookingApproval> $approvals
 * @property-read BookingApproval|null $currentApproval
 * @property-read Collection<int, ActivityLog> $activityLogs
 */
#[Fillable([
    'code',
    'requester_name',
    'requester_department',
    'purpose',
    'destination',
    'vehicle_id',
    'driver_id',
    'created_by',
    'start_at',
    'end_at',
    'status',
    'current_level',
    'odometer_start',
    'odometer_end',
    'fuel_used',
    'completed_at',
    'cancel_reason',
    'cancelled_at',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * Awalan kode sementara sebelum id booking diketahui.
     */
    public const TEMPORARY_CODE_PREFIX = 'TMP-';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => BookingStatus::class,
            'current_level' => 'integer',
            'odometer_start' => 'integer',
            'odometer_end' => 'integer',
            'fuel_used' => 'decimal:2',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Kode unik sementara untuk insert pertama; diganti lewat assignCode().
     */
    public static function temporaryCode(): string
    {
        return self::TEMPORARY_CODE_PREFIX.Str::ulid();
    }

    /**
     * Mengisi kode final berformat BK-YYYYMM-NNNNN dari bulan pembuatan dan id.
     */
    public function assignCode(): void
    {
        $this->forceFill([
            'code' => sprintf('BK-%s-%05d', $this->created_at->format('Ym'), $this->id),
        ])->save();
    }

    public function hasTemporaryCode(): bool
    {
        return str_starts_with($this->code, self::TEMPORARY_CODE_PREFIX);
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<BookingApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(BookingApproval::class)->orderBy('level');
    }

    /**
     * Approval yang sedang menunggu giliran, yaitu approval pending dengan
     * level terendah. Selama booking pending, level ini sama dengan
     * current_level; untuk booking yang sudah diputuskan hasilnya null.
     *
     * @return HasOne<BookingApproval, $this>
     */
    public function currentApproval(): HasOne
    {
        return $this->hasOne(BookingApproval::class)->ofMany(
            ['level' => 'min'],
            fn (Builder $query) => $query->where('status', ApprovalStatus::Pending),
        );
    }

    /**
     * @return MorphMany<ActivityLog, $this>
     */
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest('id');
    }

    /**
     * Booking yang masih memakai jadwal kendaraan/driver (pending atau approved).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::active());
    }

    /**
     * Booking yang jadwalnya beririsan dengan rentang waktu yang diberikan.
     * Jadwal yang hanya bersentuhan tidak dianggap beririsan.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function overlapping(Builder $query, DateTimeInterface $startAt, DateTimeInterface $endAt): void
    {
        $query->where('start_at', '<', $endAt)->where('end_at', '>', $startAt);
    }
}
