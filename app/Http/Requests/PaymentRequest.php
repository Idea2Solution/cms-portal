<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Support\Crm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'exists:invoices,id'],
            'date' => ['required', 'date'],
            'method' => ['required', Rule::in(Crm::PAYM)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(Crm::PAYSTATUS)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** The invoice must be visible to the user and a recorded payment can't exceed what is still due. */
    public function after(): array
    {
        return [function (Validator $v) {
            $invoice = Invoice::find($this->input('invoice_id'));
            if (! $invoice) {
                return;
            }
            if (! $invoice->isVisible()) {
                $v->errors()->add('invoice_id', 'That invoice belongs to another agent.');

                return;
            }
            if (in_array($invoice->state, ['Draft', 'Cancelled'], true) && $this->input('status') === 'Recorded') {
                $v->errors()->add('invoice_id', "Invoice {$invoice->code} is {$invoice->state} – issue it before recording payments.");

                return;
            }

            $payment = $this->route('payment');
            $due = round((float) $invoice->total - $invoice->paid($payment?->id ?? 0), 2);
            if ($this->input('status') === 'Recorded' && (float) $this->input('amount') > $due + 0.005) {
                $v->errors()->add('amount', 'Amount is more than the balance due ('.Crm::money($due, $invoice->currency).').');
            }
        }];
    }
}
