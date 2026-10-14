<?php

namespace App\Http\Requests\Bookings;

use App\Enums\BookingStatus;
use App\Enums\VehicleType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBookingRequest extends FormRequest
{
    /**
     * Filter yang tidak valid dibuang dengan kembali ke daftar tanpa filter.
     *
     * @var string
     */
    protected $redirectRoute = 'bookings.index';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'vehicle_type' => ['nullable', Rule::enum(VehicleType::class)],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    /**
     * Filter yang sedang aktif, lengkap dengan kunci yang kosong.
     *
     * @return array{search: string|null, status: string|null, vehicle_type: string|null, region_id: int|null, date_from: string|null, date_to: string|null}
     */
    public function filters(): array
    {
        return [
            'search' => $this->filled('search') ? $this->string('search')->trim()->toString() : null,
            'status' => $this->validated('status'),
            'vehicle_type' => $this->validated('vehicle_type'),
            'region_id' => $this->filled('region_id') ? $this->integer('region_id') : null,
            'date_from' => $this->validated('date_from'),
            'date_to' => $this->validated('date_to'),
        ];
    }
}
