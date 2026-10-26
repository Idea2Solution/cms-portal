<?php

namespace App\Http\Requests;

use App\Support\Crm;
use Illuminate\Validation\Rule;

class ProposalRequest extends LineItemsRequest
{
    public function rules(): array
    {
        return $this->itemRules() + [
            'inquiry_id' => ['nullable', Rule::exists('inquiries', 'id')->where('company_id', $this->input('company_id'))],
            'status' => ['required', Rule::in(Crm::PSTATUS)],
            'valid_until' => ['nullable', 'date', 'after_or_equal:date'],
        ];
    }
}
