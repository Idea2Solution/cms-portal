<?php

namespace App\Http\Controllers;

use App\Http\Requests\InquiryRequest;
use App\Models\Company;
use App\Models\Followup;
use App\Models\Inquiry;
use App\Support\Crm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InquiryController extends Controller
{
    public function index(Request $request): View
    {
        $q = $request->query('q');

        $inquiries = Inquiry::visible()->with('company:id,name')
            ->when($request->query('status'), fn ($w, $v) => $w->where('status', $v))
            ->when($request->query('prio'), fn ($w, $v) => $w->where('priority', $v))
            ->when($request->query('cat'), fn ($w, $v) => $w->where('category', $v))
            ->when($q, fn ($w) => $w->where(fn ($x) => $x
                ->where('code', 'like', "%{$q}%")
                ->orWhere('service', 'like', "%{$q}%")
                ->orWhere('requirement', 'like', "%{$q}%")
                ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$q}%"))))
            ->latest('date')
            ->paginate(20)
            ->withQueryString();

        return view('inquiries.index', compact('inquiries'));
    }

    public function create(Request $request): View
    {
        $company = Company::visible()->find($request->query('company'));
        $cats = array_keys(Crm::setting('services'));

        return view('inquiries.form', [
            'inquiry' => new Inquiry([
                'company_id' => $company?->id,
                'date' => now(),
                'source' => $company?->source,
                'agent' => $company?->agent ?: Crm::me(),
                'category' => $cats[0] ?? null,
                'priority' => $company?->priority ?: 'Warm',
                'status' => 'New',
            ]),
            'companies' => $this->companyOptions(),
        ]);
    }

    public function store(InquiryRequest $request): RedirectResponse
    {
        $inquiry = DB::transaction(function () use ($request) {
            $data = collect($request->validated())->except('next_fu')->all();
            $inquiry = Inquiry::create($data + [
                'code' => Crm::nextCode(Inquiry::class, 'INQ'),
                'created_by' => Crm::me(),
                'closed_at' => in_array($data['status'], ['Won', 'Lost'], true) ? today() : null,
            ]);

            $company = $inquiry->company;
            $company->log("Inquiry {$inquiry->code} created: {$inquiry->service}");
            $company->touchContacted();
            $this->afterStatus($inquiry);

            if ($request->filled('next_fu')) {
                $company->followups()->create([
                    'code' => Crm::nextCode(Followup::class, 'FU'),
                    'inquiry_id' => $inquiry->id,
                    'due' => $request->date('next_fu'),
                    'agent' => $inquiry->agent,
                    'method' => 'Phone',
                    'purpose' => 'Inquiry follow-up',
                ]);
            }

            return $inquiry;
        });

        return redirect()->route('companies.show', $inquiry->company_id)->with('ok', 'Inquiry created');
    }

    public function edit(Inquiry $inquiry): View
    {
        $this->guard($inquiry);
        return view('inquiries.form', [
            'inquiry' => $inquiry,
            'companies' => $this->companyOptions(),
        ]);
    }

    public function update(InquiryRequest $request, Inquiry $inquiry): RedirectResponse
    {
        $this->guard($inquiry);
        DB::transaction(function () use ($request, $inquiry) {
            $oldStatus = $inquiry->status;
            $inquiry->fill(collect($request->validated())->except('next_fu')->all());

            if ($oldStatus !== $inquiry->status) {
                $inquiry->closed_at = in_array($inquiry->status, ['Won', 'Lost'], true) ? today() : null;
            }
            $inquiry->save();

            if ($oldStatus !== $inquiry->status) {
                $inquiry->company->log("Inquiry {$inquiry->code} → {$inquiry->status}");
                $this->afterStatus($inquiry);
            }
        });

        return redirect()->route('companies.show', $inquiry->company_id)->with('ok', 'Inquiry updated');
    }

    public function destroy(Inquiry $inquiry): RedirectResponse
    {
        $this->guard($inquiry);
        $company = $inquiry->company;
        $inquiry->delete();
        $company->log("Inquiry {$inquiry->code} deleted");

        return redirect()->route('companies.show', $company)->with('ok', 'Inquiry deleted');
    }

    /** A won inquiry turns the lead into a customer. */
    private function afterStatus(Inquiry $inquiry): void
    {
        if ($inquiry->status === 'Won') {
            $inquiry->company->convertToCustomer();
        }
    }

    private function guard(Inquiry $inquiry): void
    {
        abort_unless($inquiry->isVisible(), 403, 'This inquiry is assigned to another agent.');
    }

    private function companyOptions(): array
    {
        return Company::visible()->orderBy('name')->get(['id', 'code', 'name'])
            ->mapWithKeys(fn ($c) => [$c->id => "{$c->name} · {$c->code}"])
            ->all();
    }
}
