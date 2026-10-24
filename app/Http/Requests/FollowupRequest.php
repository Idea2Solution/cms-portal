<?php

namespace App\Http\Requests;

use App\Support\Crm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FollowupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', Rule::exists('companies', 'id')->where(fn ($q) => Crm::isAdmin() ? $q : $q->where('agent', Crm::me()))],
            'inquiry_id' => [
                'nullable',
                Rule::exists('inquiries', 'id')->where('company_id', $this->input('company_id')),
            ],
            'due' => ['required', 'date'],
            'method' => ['required', Rule::in(Crm::METHODS)],
            'agent' => ['required', Rule::in(Crm::assignable())],
            'purpose' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(Crm::FSTATUS)],
            'outcome' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return ['inquiry_id.exists' => 'That inquiry does not belong to the selected lead.'];
    }
}
