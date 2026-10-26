<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProposalRequest;
use App\Models\Company;
use App\Models\Inquiry;
use App\Models\Proposal;
use App\Support\CompanyOptions;
use App\Support\Crm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProposalController extends Controller
{
    public function create(Request $request): View
    {
        $inquiry = Inquiry::visible()->find($request->query('inquiry'));
        $company = $inquiry?->company ?? Company::visible()->find($request->query('company'));

        return view('proposals.form', [
            'proposal' => new Proposal([
                'company_id' => $company?->id,
                'inquiry_id' => $inquiry?->id,
                'agent' => $company?->agent ?: Crm::me(),
                'status' => 'Draft',
                'date' => today(),
                'valid_until' => today()->addDays(14),
                'currency' => Crm::setting('currency'),
                'tax_pct' => Crm::setting('taxPct'),
                'discount' => 0,
                'terms' => Crm::setting('terms'),
                // start from what the customer asked for
                'items' => $inquiry ? [[
                    'service' => $inquiry->service,
                    'desc' => \Illuminate\Support\Str::limit((string) $inquiry->requirement, 140),
                    'qty' => 1,
                    'price' => $inquiry->budget_to ?: $inquiry->budget_from,
                ]] : [],
            ]),
            'companies' => CompanyOptions::forForms(),
        ]);
    }

    public function store(ProposalRequest $request): RedirectResponse
    {
        $proposal = DB::transaction(function () use ($request) {
            $proposal = Proposal::create($request->documentData() + [
                'code' => Crm::nextCode(Proposal::class, 'PR'),
                'created_by' => Crm::me(),
            ]);
            $proposal->company->log("Proposal {$proposal->code} created (".Crm::money($proposal->total, $proposal->currency).')');
            if ($proposal->status !== 'Draft') {
                $proposal->company->log("Proposal {$proposal->code} → {$proposal->status}");
            }
            $this->afterStatus($proposal, null);

            return $proposal;
        });

        return redirect()->route('proposals.edit', $proposal)->with('ok', 'Proposal saved');
    }

    public function edit(Proposal $proposal): View
    {
        $this->guard($proposal);

        return view('proposals.form', [
            'proposal' => $proposal->load('invoices:id,proposal_id,code'),
            'companies' => CompanyOptions::forForms(),
        ]);
    }

    public function update(ProposalRequest $request, Proposal $proposal): RedirectResponse
    {
        $this->guard($proposal);

        DB::transaction(function () use ($request, $proposal) {
            $old = $proposal->status;
            $proposal->update($request->documentData());
            if ($old !== $proposal->status) {
                $proposal->company->log("Proposal {$proposal->code} → {$proposal->status}");
                $this->afterStatus($proposal, $old);
            }
        });

        return redirect()->route('proposals.edit', $proposal)->with('ok', 'Proposal saved');
    }

    public function destroy(Proposal $proposal): RedirectResponse
    {
        $this->guard($proposal);
        $company = $proposal->company;
        $proposal->delete();
        $company->log("Proposal {$proposal->code} deleted");

        return redirect()->route('companies.show', $company)->with('ok', 'Proposal deleted');
    }

    /** Keep the linked inquiry in step with the proposal, and convert on acceptance. */
    private function afterStatus(Proposal $p, ?string $old): void
    {
        if ($p->status === $old) {
            return;
        }

        $inq = $p->inquiry;
        if ($inq) {
            $new = match (true) {
                $p->status === 'Sent' && in_array($inq->status, ['New', 'In Progress', 'Proposal Required'], true) => 'Proposal Sent',
                in_array($p->status, ['Under Discussion', 'Negotiation'], true) && ! in_array($inq->status, ['Won', 'Lost'], true) => 'Negotiation',
                $p->status === 'Accepted' => 'Won',
                $p->status === 'Rejected' && $inq->status !== 'Won' => 'Lost',
                default => null,
            };
            if ($new && $new !== $inq->status) {
                $inq->update(['status' => $new, 'closed_at' => in_array($new, ['Won', 'Lost'], true) ? today() : $inq->closed_at]);
                $p->company->log("Inquiry {$inq->code} → {$new}");
            }
        }

        if ($p->status === 'Accepted') {
            $p->company->convertToCustomer();
        }
    }

    private function guard(Proposal $proposal): void
    {
        abort_unless($proposal->isVisible(), 403, 'This proposal belongs to another agent.');
    }
}
