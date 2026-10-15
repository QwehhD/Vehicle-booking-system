<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use App\Enums\VehicleType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    /**
     * Filter yang tidak valid dibuang dengan kembali ke laporan bulan berjalan.
     *
     * @var string
     */
    protected $redirectRoute = 'reports.index';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'vehicle_type' => ['nullable', Rule::enum(VehicleType::class)],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
        ];
    }

    /**
     * Filter laporan; rentang tanggal bawaan adalah bulan berjalan.
     *
     * @return array{date_from: string, date_to: string, status: string|null, vehicle_type: string|null, region_id: int|null}
     */
    public function filters(): array
    {
        return [
            'date_from' => $this->filled('date_from')
                ? $this->string('date_from')->toString()
                : now()->startOfMonth()->toDateString(),
            'date_to' => $this->filled('date_to')
                ? $this->string('date_to')->toString()
                : now()->endOfMonth()->toDateString(),
            'status' => $this->filled('status') ? $this->string('status')->toString() : null,
            'vehicle_type' => $this->filled('vehicle_type') ? $this->string('vehicle_type')->toString() : null,
            'region_id' => $this->filled('region_id') ? $this->integer('region_id') : null,
        ];
    }
}
