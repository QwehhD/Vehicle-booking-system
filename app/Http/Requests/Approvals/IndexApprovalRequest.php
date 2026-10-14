<?php

namespace App\Http\Requests\Approvals;

use App\Enums\ApprovalTab;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexApprovalRequest extends FormRequest
{
    /**
     * Tab yang tidak dikenal dibuang dengan kembali ke tab bawaan.
     *
     * @var string
     */
    protected $redirectRoute = 'approvals.index';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tab' => ['nullable', Rule::enum(ApprovalTab::class)],
        ];
    }

    public function tab(): ApprovalTab
    {
        return $this->enum('tab', ApprovalTab::class) ?? ApprovalTab::Waiting;
    }
}
