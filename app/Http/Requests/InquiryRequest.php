<?php

namespace App\Http\Requests;

use App\Support\Crm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $services = Crm::setting('services');

        return [
            'company_id' => ['required', Rule::exists('companies', 'id')->where(fn ($q) => Crm::isAdmin() ? $q : $q->where('agent', Crm::me()))],
            'date' => ['nullable', 'date'],
            'source' => ['nullable', Rule::in(Crm::SOURCES)],
            'agent' => ['required', Rule::in(Crm::assignable())],
            'priority' => ['required', Rule::in(Crm::PRIOS)],
            'status' => ['required', Rule::in(Crm::ISTATUS)],
            'category' => ['required', Rule::in(array_keys($services))],
            'service' => ['required', Rule::in($services[$this->input('category')] ?? [])],
            'requirement' => ['required', 'string', 'max:5000'],
            'budget_from' => ['nullable', 'numeric', 'min:0'],
            'budget_to' => ['nullable', 'numeric', 'min:0', 'gte:budget_from'],
            'start_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'next_fu' => ['nullable', 'date'],
        ];
    }
}
