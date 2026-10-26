<?php

namespace App\Http\Requests;

use App\Support\Crm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $company = $this->route('company');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('companies', 'name')->ignore($company?->id)],
            'category' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'contact' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'phone2' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'preferred' => ['nullable', Rule::in(Crm::METHODS)],
            'socials' => ['nullable', 'array'],
            'socials.*.network' => ['required_with:socials.*.url', Rule::in(Crm::SOCIALS)],
            'socials.*.url' => ['nullable', 'string', 'max:255'],
            'source' => ['required', Rule::in(Crm::SOURCES)],
            'source_url' => ['nullable', 'string', 'max:255'],
            'agent' => ['required', Rule::in(Crm::assignable())],
            'priority' => ['required', Rule::in(Crm::PRIOS)],
            'status' => ['required', Rule::in(Crm::LSTATUS)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'next_fu' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'company name',
            'category' => 'business category',
            'contact' => 'contact person',
            'phone' => 'primary phone',
            'phone2' => 'secondary phone',
            'source' => 'lead source',
            'agent' => 'assigned agent',
            'priority' => 'temperature',
            'next_fu' => 'follow-up date',
            'socials.*.url' => 'social media link',
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'A lead or customer with this name already exists.'];
    }

    /** Company attributes, with empty social rows dropped. */
    public function companyData(): array
    {
        $data = collect($this->validated())->except('next_fu')->all();
        $data['socials'] = collect($data['socials'] ?? [])
            ->filter(fn ($s) => ! empty($s['url']))
            ->map(fn ($s) => ['network' => $s['network'], 'url' => $s['url']])
            ->values()
            ->all();

        return $data;
    }
}
