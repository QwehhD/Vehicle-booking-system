<?php

namespace App\Http\Requests\Bookings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteBookingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'odometer_start' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'odometer_end' => ['required', 'integer', 'gte:odometer_start', 'max:4294967295'],
            'fuel_used' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'odometer_end.gte' => 'Odometer akhir tidak boleh lebih kecil dari odometer awal.',
            'fuel_used.gt' => 'BBM terpakai harus lebih dari 0 liter.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'odometer_start' => 'odometer awal',
            'odometer_end' => 'odometer akhir',
            'fuel_used' => 'BBM terpakai',
        ];
    }

    /**
     * Data penyelesaian yang sudah tervalidasi dengan tipe yang pasti.
     *
     * @return array{odometer_start: int, odometer_end: int, fuel_used: float}
     */
    public function completionData(): array
    {
        return [
            'odometer_start' => $this->integer('odometer_start'),
            'odometer_end' => $this->integer('odometer_end'),
            'fuel_used' => $this->float('fuel_used'),
        ];
    }
}
