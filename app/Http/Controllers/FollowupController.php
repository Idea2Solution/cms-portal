<?php

namespace App\Http\Controllers;

use App\Http\Requests\FollowupRequest;
use App\Models\Company;
use App\Models\Followup;
use App\Support\Crm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FollowupController extends Controller
{
    public const TABS = [
        'overdue' => 'Overdue',
        'due' => 'Due today & overdue',
        'upcoming' => 'Upcoming',
        'done' => 'Done / closed',
    ];

    public function index(Request $request): View
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'due';
        $agent = $request->query('agent');

        $base = fn () => Followup::visible()->when($agent, fn ($q) => $q->where('agent', $agent));
        $scopes = [
            'overdue' => fn (Builder $q) => $q->pending()->where('due', '<', now()),
            'due' => fn (Builder $q) => $q->pending()->whereDate('due', '<=', today()),
            'upcoming' => fn (Builder $q) => $q->pending()->whereDate('due', '>', today()),
            'done' => fn (Builder $q) => $q->where('status', '!=', 'Pending'),
        ];

        $counts = collect($scopes)->map(fn ($s) => $s($base())->count());

        $query = $scopes[$tab]($base())->with(['company:id,name', 'inquiry:id,code']);
        $tab === 'done'
            ? $query->orderByDesc(DB::raw('COALESCE(completed_at, due)'))
            : $query->orderBy('due');

        return view('followups.index', [
            'followups' => $query->paginate(20)->withQueryString(),
            'tab' => $tab,
            'counts' => $counts,
        ]);
    }

    public function create(Request $request): View
    {
        $company = Company::visible()->find($request->query('company'));

        return view('followups.form', [
            'followup' => new Followup([
                'company_id' => $company?->id,
                'inquiry_id' => $request->query('inquiry'),
                'agent' => $company?->agent ?: Crm::me(),
                'due' => today()->addDay()->setTime(10, 0),
                'method' => $company?->preferred ?: 'Phone',
                'status' => 'Pending',
            ]),
            'companies' => $this->companyOptions(),
        ]);
    }

    public function store(FollowupRequest $request): RedirectResponse
    {
        $followup = Followup::create($request->validated() + [
            'code' => Crm::nextCode(Followup::class, 'FU'),
            'status' => 'Pending',
        ]);

        return redirect()->route('companies.show', $followup->company_id)->with('ok', 'Follow-up scheduled');
    }

    public function edit(Followup $followup): View
    {
        $this->guard($followup);
        return view('followups.form', [
            'followup' => $followup,
            'companies' => $this->companyOptions(),
        ]);
    }

    public function update(FollowupRequest $request, Followup $followup): RedirectResponse
    {
        $this->guard($followup);
        $followup->fill($request->validated());
        if ($followup->isDirty('status')) {
            $followup->completed_at = $followup->status === 'Completed' ? now() : null;
        }
        $followup->save();

        return redirect()->route('followups.index')->with('ok', 'Follow-up saved');
    }

    public function destroy(Followup $followup): RedirectResponse
    {
        $this->guard($followup);
        $followup->delete();

        return redirect()->route('followups.index')->with('ok', 'Follow-up deleted');
    }

    /** Mark done, write the outcome to the timeline and optionally book the next one. */
    public function complete(Request $request, Followup $followup): RedirectResponse
    {
        $this->guard($followup);
        $data = $request->validate([
            'outcome' => ['nullable', 'string', 'max:5000'],
            'next_due' => ['nullable', 'date'],
            'next_purpose' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($followup, $data) {
            $followup->update(['status' => 'Completed', 'outcome' => $data['outcome'] ?? null, 'completed_at' => now()]);

            $company = $followup->company;
            $company->touchContacted();

            if (! empty($data['outcome'])) {
                $kind = ['Phone' => 'Call', 'Video Call' => 'Meeting', 'WhatsApp' => 'WhatsApp', 'Email' => 'Email', 'Meeting' => 'Meeting'][$followup->method] ?? 'Note';
                $company->log('Follow-up done ('.($followup->purpose ?: $followup->method)."): {$data['outcome']}", $kind);
            }

            if (! empty($data['next_due'])) {
                $company->followups()->create([
                    'code' => Crm::nextCode(Followup::class, 'FU'),
                    'inquiry_id' => $followup->inquiry_id,
                    'due' => $data['next_due'],
                    'agent' => $followup->agent,
                    'method' => $followup->method,
                    'purpose' => $data['next_purpose'] ?: 'Follow-up',
                ]);
            }
        });

        return back()->with('ok', empty($data['next_due']) ? 'Follow-up completed' : 'Done – next follow-up scheduled');
    }

    private function guard(Followup $followup): void
    {
        abort_unless($followup->isVisible(), 403, 'This follow-up is assigned to another agent.');
    }

    /** Companies with their inquiries, for the form's dependent dropdowns. */
    private function companyOptions(): array
    {
        return Company::visible()->orderBy('name')
            ->with('inquiries:id,company_id,code,service')
            ->get(['id', 'code', 'name', 'agent'])
            ->mapWithKeys(fn ($c) => [$c->id => [
                'label' => "{$c->name} · {$c->code}",
                'agent' => $c->agent,
                'inquiries' => $c->inquiries->map(fn ($i) => ['id' => $i->id, 'label' => "{$i->code} · {$i->service}"])->values(),
            ]])
            ->all();
    }
}
