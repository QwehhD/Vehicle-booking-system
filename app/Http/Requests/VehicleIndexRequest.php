<?php

namespace App\Http\Requests;

use App\Enums\VehicleOwnership;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleIndexRequest extends FormRequest
{
    /**
     * Filter yang tidak valid dibuang dengan kembali ke daftar tanpa filter.
     *
     * @var string
     */
    protected $redirectRoute = 'vehicles.index';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(VehicleType::class)],
            'ownership' => ['nullable', Rule::enum(VehicleOwnership::class)],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'status' => ['nullable', Rule::enum(VehicleStatus::class)],
        ];
    }

    /**
     * @return array{type: string|null, ownership: string|null, location_id: int|null, status: string|null}
     */
    public function filters(): array
    {
        return [
            'type' => $this->filled('type') ? $this->string('type')->toString() : null,
            'ownership' => $this->filled('ownership') ? $this->string('ownership')->toString() : null,
            'location_id' => $this->filled('location_id') ? $this->integer('location_id') : null,
            'status' => $this->filled('status') ? $this->string('status')->toString() : null,
        ];
    }
}
