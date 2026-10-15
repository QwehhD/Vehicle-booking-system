<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
{
    /**
     * Filter yang tidak valid dibuang dengan kembali ke dashboard tanpa filter.
     *
     * @var string
     */
    protected $redirectRoute = 'dashboard';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
        ];
    }

    /**
     * Filter aktif; tahun bawaan adalah tahun berjalan.
     *
     * @return array{year: int, region_id: int|null}
     */
    public function filters(): array
    {
        return [
            'year' => $this->filled('year') ? $this->integer('year') : now()->year,
            'region_id' => $this->filled('region_id') ? $this->integer('region_id') : null,
        ];
    }
}
