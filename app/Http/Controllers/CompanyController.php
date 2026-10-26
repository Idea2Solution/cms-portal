<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadRequest;
use App\Models\Company;
use App\Models\Followup;
use App\Support\Crm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function leads(Request $request): View
    {
        $f = $request->only(['q', 'prio', 'status', 'agent', 'src', 'arch']);

        $leads = Company::leads()->visible()
            ->search($f['q'] ?? null)
            ->when($f['prio'] ?? null, fn ($q, $v) => $q->where('priority', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['agent'] ?? null, fn ($q, $v) => $q->where('agent', $v))
            ->when($f['src'] ?? null, fn ($q, $v) => $q->where('source', $v))
            // archived leads stay hidden unless asked for, searched for, or filtered on
            ->when(empty($f['arch']) && empty($f['q']) && ($f['status'] ?? '') !== 'Archived', fn ($q) => $q->where('status', '!=', 'Archived'))
            ->addSelect(['next_due' => Followup::select('due')
                ->whereColumn('company_id', 'companies.id')
                ->where('status', 'Pending')
                ->orderBy('due')
                ->limit(1)])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('leads.index', compact('leads'));
    }

    public function customers(Request $request): View|RedirectResponse
    {
        if (! Crm::customersOn()) {
            return redirect()->route('leads.index');
        }

        $customers = Company::customers()->visible()
            ->search($request->query('q'))
            ->withCount(['inquiries as open_inquiries_count' => fn ($q) => $q->open()])
            ->withCount('inquiries')
            ->orderByDesc('converted_at')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        if ($q === '') {
            return response()->json([]);
        }

        $rows = Company::visible()->search($q)->limit(8)->get(['id', 'name', 'type', 'contact', 'phone'])
            ->map(fn (Company $c) => [
                'name' => $c->name,
                'type' => $c->isCustomer() ? 'Customer' : 'Lead',
                'contact' => $c->contact,
                'phone' => $c->phone,
                'url' => route('companies.show', $c),
            ]);

        return response()->json($rows);
    }

    public function create(): View
    {
        return view('companies.form', [
            'company' => new Company(['priority' => 'Warm', 'status' => 'Not Contacted', 'agent' => Crm::me(), 'socials' => []]),
        ]);
    }

    public function store(LeadRequest $request): RedirectResponse
    {
        $company = DB::transaction(function () use ($request) {
            $company = Company::create($request->companyData() + [
                'code' => Crm::nextCode(Company::class, 'P'),
                'type' => 'Prospect',
                'created_by' => Crm::me(),
            ]);
            $company->log("Lead created · source: {$company->source} · temperature: {$company->priority}");
            $this->scheduleFromForm($request, $company);

            return $company;
        });

        return redirect()->route('companies.show', $company)->with('ok', 'Lead created');
    }

    public function show(Company $company): View
    {
        $this->guard($company);
        $company->load([
            'inquiries' => fn ($q) => $q->latest('date'),
            'followups' => fn ($q) => $q->orderByRaw("status <> 'Pending'")->orderBy('due'),
            'remarks' => fn ($q) => $q->latest()->latest('id'),
            'proposals' => fn ($q) => $q->latest('date'),
            'invoices' => fn ($q) => $q->withPaid()->latest('date'),
            'payments' => fn ($q) => $q->with('invoice:id,currency')->latest('date'),
            'documents' => fn ($q) => $q->latest('date'),
        ]);

        return view('companies.show', compact('company'));
    }

    public function edit(Company $company): View
    {
        $this->guard($company);
        return view('companies.form', compact('company'));
    }

    public function update(LeadRequest $request, Company $company): RedirectResponse
    {
        $this->guard($company);
        DB::transaction(function () use ($request, $company) {
            $old = $company->only(['status', 'priority', 'agent']);
            $company->update($request->companyData());

            if ($old['status'] !== $company->status) {
                $company->log("Status changed: {$old['status']} → {$company->status}");
            }
            if ($old['priority'] !== $company->priority) {
                $company->log("Temperature changed: {$old['priority']} → {$company->priority}");
            }
            if ($old['agent'] !== $company->agent) {
                $company->log("Reassigned: {$old['agent']} → {$company->agent}");
            }
            $this->scheduleFromForm($request, $company);
        });

        return redirect()->route('companies.show', $company)->with('ok', ($company->isCustomer() ? 'Customer' : 'Lead').' updated');
    }

    public function destroy(Company $company): RedirectResponse
    {
        abort_unless(Crm::isAdmin(), 403, 'Only admins can delete records.');
        $wasCustomer = $company->isCustomer();
        // document rows cascade in the database; their uploaded files have to go by hand
        foreach ($company->documents as $doc) {
            foreach ($doc->files ?? [] as $f) {
                Storage::disk('local')->delete($f['path']);
            }
        }
        $company->delete();

        return redirect()->route($wasCustomer ? 'customers.index' : 'leads.index')->with('ok', "{$company->name} deleted");
    }

    public function convert(Company $company): RedirectResponse
    {
        $this->guard($company);
        abort_unless(Crm::customersOn(), 404);
        $company->convertToCustomer();

        return back()->with('ok', "{$company->name} is now customer {$company->customer_no}");
    }

    public function archive(Company $company): RedirectResponse
    {
        $this->guard($company);
        if ($company->status === 'Archived') {
            $company->update(['status' => $company->last_contacted ? 'Contacted' : 'Not Contacted']);
            $company->log('Restored from archive');

            return back()->with('ok', 'Restored');
        }

        $company->update(['status' => 'Archived']);
        $company->log('Archived');

        return back()->with('ok', 'Archived');
    }

    public function remark(Request $request, Company $company): RedirectResponse
    {
        $this->guard($company);
        $data = $request->validate([
            'kind' => ['required', Rule::in(Crm::REMARK_KINDS)],
            'text' => ['required', 'string', 'max:5000'],
        ]);

        $company->log($data['text'], $data['kind']);
        if ($data['kind'] !== 'Note') {
            $company->touchContacted();
        }

        return back()->with('ok', 'Remark added');
    }

    private function guard(Company $company): void
    {
        abort_unless($company->isVisible(), 403, 'This record is assigned to another agent.');
    }

    private function scheduleFromForm(LeadRequest $request, Company $company): void
    {
        if (! $request->filled('next_fu')) {
            return;
        }

        $company->followups()->create([
            'code' => Crm::nextCode(Followup::class, 'FU'),
            'due' => $request->date('next_fu'),
            'agent' => $company->agent,
            'method' => $company->preferred ?: 'Phone',
            'purpose' => 'Follow-up',
        ]);
    }
}
