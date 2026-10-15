<?php

namespace App\Http\Requests;

use App\Enums\ActivityAction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityLogRequest extends FormRequest
{
    /**
     * Filter yang tidak valid dibuang dengan kembali ke log tanpa filter.
     *
     * @var string
     */
    protected $redirectRoute = 'activity-logs.index';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', Rule::enum(ActivityAction::class)],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    /**
     * @return array{search: string|null, user_id: int|null, action: string|null, date_from: string|null, date_to: string|null}
     */
    public function filters(): array
    {
        return [
            'search' => $this->filled('search') ? $this->string('search')->trim()->toString() : null,
            'user_id' => $this->filled('user_id') ? $this->integer('user_id') : null,
            'action' => $this->filled('action') ? $this->string('action')->toString() : null,
            'date_from' => $this->filled('date_from') ? $this->string('date_from')->toString() : null,
            'date_to' => $this->filled('date_to') ? $this->string('date_to')->toString() : null,
        ];
    }
}
