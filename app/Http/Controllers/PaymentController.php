<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Crm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function create(Request $request): View
    {
        $invoice = Invoice::visible()->withPaid()->find($request->query('invoice'));

        return view('payments.form', [
            'payment' => new Payment([
                'invoice_id' => $invoice?->id,
                'date' => today(),
                'method' => 'Bank Transfer',
                'amount' => $invoice?->balance() ?: null,
                'status' => 'Recorded',
            ]),
            'invoices' => $this->invoiceOptions($invoice?->id),
        ]);
    }

    public function store(PaymentRequest $request): RedirectResponse
    {
        $invoice = Invoice::findOrFail($request->input('invoice_id'));
        $payment = Payment::create($request->validated() + [
            'code' => Crm::nextCode(Payment::class, 'PAY'),
            'company_id' => $invoice->company_id,
            'created_by' => Crm::me(),
        ]);
        $invoice->company->log("Payment {$payment->code} received: ".Crm::money($payment->amount, $invoice->currency)." for {$invoice->code}");

        $msg = $invoice->fresh()->status() === 'Paid' ? 'Payment recorded – invoice fully paid ✔' : 'Payment recorded';

        return redirect()->route('invoices.edit', $invoice)->with('ok', $msg);
    }

    public function edit(Payment $payment): View
    {
        $this->guard($payment);

        return view('payments.form', [
            'payment' => $payment,
            'invoices' => $this->invoiceOptions($payment->invoice_id),
        ]);
    }

    public function update(PaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->guard($payment);

        $old = $payment->status;
        $invoice = Invoice::findOrFail($request->input('invoice_id'));
        $payment->update($request->validated() + ['company_id' => $invoice->company_id]);
        if ($old !== $payment->status) {
            $invoice->company->log("Payment {$payment->code} → {$payment->status}");
        }

        return redirect()->route('invoices.edit', $invoice)->with('ok', 'Payment saved');
    }

    /** Invoices that can take a payment, plus the one already selected. */
    private function invoiceOptions(?int $selected): array
    {
        return Invoice::visible()->withPaid()->with('company:id,name')
            ->where(fn ($q) => $q->where('state', 'Issued')->when($selected, fn ($w) => $w->orWhere('id', $selected)))
            ->latest('date')
            ->get()
            ->filter(fn (Invoice $i) => $i->id === $selected || $i->balance() > 0)
            ->mapWithKeys(fn (Invoice $i) => [$i->id => [
                'label' => "{$i->code} · {$i->company->name} · balance ".Crm::money($i->balance(), $i->currency),
                'total' => (float) $i->total,
                'paid' => $i->paid(),
                'balance' => $i->balance(),
                'currency' => $i->currency,
            ]])
            ->all();
    }

    private function guard(Payment $payment): void
    {
        abort_unless($payment->isVisible(), 403, 'This payment belongs to another agent.');
    }
}
