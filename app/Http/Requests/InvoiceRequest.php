<?php

namespace App\Http\Requests;

use App\Support\Crm;
use Illuminate\Validation\Rule;

class InvoiceRequest extends LineItemsRequest
{
    public function rules(): array
    {
        return $this->itemRules() + [
            'proposal_id' => ['nullable', Rule::exists('proposals', 'id')->where('company_id', $this->input('company_id'))],
            'project' => ['nullable', 'string', 'max:255'],
            'state' => ['required', Rule::in(Crm::ISTATES)],
            'due' => ['nullable', 'date', 'after_or_equal:date'],
        ];
    }
}
