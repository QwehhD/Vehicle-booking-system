<?php

namespace App\Http\Requests\Approvals;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApproveRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'note' => 'catatan',
        ];
    }

    public function note(): ?string
    {
        return $this->filled('note') ? $this->string('note')->trim()->toString() : null;
    }
}
