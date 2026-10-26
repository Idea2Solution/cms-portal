<?php

namespace App\Http\Requests;

use App\Support\Crm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Shared rules for documents with line items (proposals, invoices). */
abstract class LineItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Drop completely empty rows the editor leaves behind. */
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($i) => filled($i['service'] ?? null) || filled($i['desc'] ?? null) || filled($i['price'] ?? null))
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    protected function itemRules(): array
    {
        return [
            'company_id' => ['required', Crm::visibleCompanyRule()],
            'agent' => ['required', Rule::in(Crm::assignable())],
            'date' => ['required', 'date'],
            'currency' => ['required', Rule::in(Crm::CURS)],
            'tax_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service' => ['nullable', 'string', 'max:255'],
            'items.*.desc' => ['nullable', 'string', 'max:2000'],
            'items.*.qty' => ['required', 'numeric', 'min:0'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one line item.',
            'items.min' => 'Add at least one line item.',
            'items.*.price.required' => 'Every line needs a unit price.',
        ];
    }

    /** Validated data with numeric defaults filled in. */
    public function documentData(): array
    {
        $data = $this->validated();
        $data['tax_pct'] = (float) ($data['tax_pct'] ?? 0);
        $data['discount'] = (float) ($data['discount'] ?? 0);
        $data['items'] = array_map(fn ($i) => [
            'service' => $i['service'] ?? '',
            'desc' => $i['desc'] ?? '',
            'qty' => (float) $i['qty'],
            'price' => (float) $i['price'],
        ], $data['items']);

        return $data;
    }
}
