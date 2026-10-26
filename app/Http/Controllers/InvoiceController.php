<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Support\CompanyOptions;
use App\Support\Crm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function create(Request $request): View
    {
        $proposal = Proposal::visible()->with('inquiry:id,service')->find($request->query('proposal'));
        $company = $proposal?->company ?? Company::visible()->find($request->query('company'));

        return view('invoices.form', [
            'invoice' => new Invoice([
                'company_id' => $company?->id,
                'proposal_id' => $proposal?->id,
                'project' => $proposal?->inquiry?->service,
                'agent' => $company?->agent ?: Crm::me(),
                'state' => 'Issued',
                'date' => today(),
                'due' => today()->addDays(14),
                'currency' => $proposal?->currency ?? Crm::setting('currency'),
                'tax_pct' => $proposal?->tax_pct ?? Crm::setting('taxPct'),
                'discount' => $proposal?->discount ?? 0,
                'terms' => $proposal?->terms ?? Crm::setting('terms'),
                'items' => $proposal?->items ?? [],
            ]),
            'companies' => CompanyOptions::forForms(),
        ]);
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $invoice = Invoice::create($request->documentData() + [
            'code' => Crm::nextCode(Invoice::class, 'INV'),
            'created_by' => Crm::me(),
        ]);
        $invoice->company->log("Invoice {$invoice->code} ".($invoice->state === 'Draft' ? 'drafted' : 'issued').': '.Crm::money($invoice->total, $invoice->currency));

        return redirect()->route('invoices.edit', $invoice)->with('ok', 'Invoice saved');
    }

    public function edit(Invoice $invoice): View
    {
        $this->guard($invoice);

        return view('invoices.form', [
            'invoice' => $invoice->load(['payments' => fn ($q) => $q->orderBy('date')]),
            'companies' => CompanyOptions::forForms(),
        ]);
    }

    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->guard($invoice);

        $old = $invoice->state;
        $invoice->update($request->documentData());
        if ($old !== $invoice->state) {
            $invoice->company->log("Invoice {$invoice->code} → {$invoice->state}");
        }

        return redirect()->route('invoices.edit', $invoice)->with('ok', 'Invoice saved');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->guard($invoice);
        abort_unless(Crm::isAdmin(), 403, 'Only admins can delete invoices – cancel it instead.');

        $company = $invoice->company;
        $invoice->delete();
        $company->log("Invoice {$invoice->code} deleted");

        return redirect()->route('companies.show', $company)->with('ok', 'Invoice deleted');
    }

    private function guard(Invoice $invoice): void
    {
        abort_unless($invoice->isVisible(), 403, 'This invoice belongs to another agent.');
    }
}
