<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Followup;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Proposal;
use App\Support\Crm;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $period = in_array((int) $request->query('period', 30), [7, 30, 90, 0], true) ? (int) $request->query('period', 30) : 30;
        $from = $period ? today()->subDays($period) : null;

        $overdue = Followup::visible()->pending()->where('due', '<', now());
        $today = Followup::visible()->pending()->whereDate('due', today());

        $closedIn = fn (string $status) => Inquiry::visible()->where('status', $status)
            ->when($from, fn ($q) => $q->where('closed_at', '>=', $from))
            ->count();

        $kpis = [
            'newLeads' => Company::visible()->leads()->when($from, fn ($q) => $q->where('created_at', '>=', $from))->count(),
            'hot' => Company::visible()->leads()->live()->where('priority', 'Hot')->count(),
            'notContacted' => Company::visible()->leads()->live()->where('status', 'Not Contacted')->count(),
            'overdue' => (clone $overdue)->count(),
            'today' => (clone $today)->count(),
            'openInquiries' => Inquiry::visible()->open()->count(),
            'won' => $closedIn('Won'),
            'lost' => $closedIn('Lost'),
            'customers' => Company::visible()->customers()->count(),
            'proposalsSent' => Proposal::visible()->where('status', '!=', 'Draft')
                ->when($from, fn ($q) => $q->where('date', '>=', $from))->count(),
            'pipelineValue' => Crm::sumByCurrency(Proposal::visible()
                ->whereIn('status', ['Sent', 'Under Discussion', 'Negotiation'])
                ->get(['currency', 'total'])->map(fn ($p) => [$p->currency, $p->total])),
            'outstanding' => Crm::sumByCurrency(Invoice::visible()->open()->withPaid()->get()
                ->map(fn ($i) => [$i->currency, $i->balance()])),
            'received' => Crm::sumByCurrency(Payment::visible()->where('status', 'Recorded')
                ->when($from, fn ($q) => $q->where('date', '>=', $from))
                ->with('invoice:id,currency')->get()
                ->map(fn ($p) => [$p->invoice->currency, $p->amount])),
        ];

        // Pipeline strip uses the same grouping as the board
        $stages = array_map(fn ($cards) => $cards->count(), PipelineController::board());

        $followups = Followup::visible()->pending()
            ->whereDate('due', '<=', today())
            ->with('company:id,name')
            ->orderBy('due')
            ->limit(8)
            ->get();

        $hotLeads = Company::visible()->leads()->live()->where('priority', 'Hot')
            ->orderByRaw('last_contacted IS NOT NULL, last_contacted')
            ->limit(6)
            ->get();

        return view('dashboard', [
            'period' => $period,
            'kpis' => $kpis,
            'stages' => $stages,
            'followups' => $followups,
            'hotLeads' => $hotLeads,
            'empty' => ! Company::visible()->exists(),
            'label' => $period ? $period.'d' : 'all',
        ]);
    }
}
