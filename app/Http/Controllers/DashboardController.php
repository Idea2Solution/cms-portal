<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Followup;
use App\Models\Inquiry;
use App\Support\Crm;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public const STAGES = ['New Lead', 'Contacted', 'Qualified', 'Inquiry', 'Proposal', 'Negotiation', 'Won', 'Lost'];

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
        ];

        // Pipeline strip: Won/Lost only count when closed in the last 30 days, like the board in the design
        $stages = array_fill_keys(self::STAGES, 0);
        $cut = today()->subDays(30);
        Company::visible()->live()->with('inquiries:id,company_id,status,closed_at')->get()->each(function (Company $c) use (&$stages, $cut) {
            $stage = $c->stage();
            if (in_array($stage, ['Won', 'Lost'], true)) {
                $closed = $c->inquiries->where('status', $stage)->max('closed_at') ?? $c->converted_at;
                if ($closed && $closed->lt($cut)) {
                    return;
                }
            }
            $stages[$stage]++;
        });

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
