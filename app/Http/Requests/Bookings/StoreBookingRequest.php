<?php

namespace App\Http\Requests\Bookings;

use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_department' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:2000'],
            'destination' => ['required', 'string', 'max:255'],
            'start_at' => ['required', 'date', 'after:now'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')->whereNot('status', VehicleStatus::Maintenance->value),
            ],
            'driver_id' => [
                'required',
                'integer',
                Rule::exists('drivers', 'id')->where('is_active', true),
            ],
            'approvers' => ['required', 'array', 'min:2', 'max:5'],
            'approvers.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where('role', UserRole::Approver->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_at.after' => 'Waktu mulai harus setelah waktu sekarang.',
            'end_at.after' => 'Waktu selesai harus setelah waktu mulai.',
            'vehicle_id.exists' => 'Kendaraan tidak ditemukan atau sedang dalam perawatan.',
            'driver_id.exists' => 'Driver tidak ditemukan atau sedang nonaktif.',
            'approvers.required' => 'Pilih minimal 2 approver.',
            'approvers.min' => 'Pilih minimal 2 approver.',
            'approvers.max' => 'Pilih maksimal 5 approver.',
            'approvers.*.required' => 'Pilih approver untuk level ini.',
            'approvers.*.distinct' => 'Approver ini sudah dipilih di level lain.',
            'approvers.*.exists' => 'Pengguna ini bukan approver.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'requester_name' => 'nama pemohon',
            'requester_department' => 'departemen',
            'purpose' => 'keperluan',
            'destination' => 'tujuan',
            'start_at' => 'waktu mulai',
            'end_at' => 'waktu selesai',
            'vehicle_id' => 'kendaraan',
            'driver_id' => 'driver',
            'approvers' => 'approver',
        ];
    }

    /**
     * Data pemesanan yang sudah tervalidasi dengan tipe yang pasti.
     *
     * @return array{requester_name: string, requester_department: string, purpose: string, destination: string, start_at: string, end_at: string, vehicle_id: int, driver_id: int, approvers: list<int>}
     */
    public function bookingData(): array
    {
        return [
            'requester_name' => $this->string('requester_name')->toString(),
            'requester_department' => $this->string('requester_department')->toString(),
            'purpose' => $this->string('purpose')->toString(),
            'destination' => $this->string('destination')->toString(),
            'start_at' => $this->string('start_at')->toString(),
            'end_at' => $this->string('end_at')->toString(),
            'vehicle_id' => $this->integer('vehicle_id'),
            'driver_id' => $this->integer('driver_id'),
            'approvers' => array_values($this->collect('approvers')->map(fn (mixed $id) => intval($id))->all()),
        ];
    }
}
