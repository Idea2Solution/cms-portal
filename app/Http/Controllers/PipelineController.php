<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\Crm;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PipelineController extends Controller
{
    public function __invoke(Request $request): View
    {
        $agent = Crm::isAdmin() ? $request->query('agent') : null;

        return view('pipeline', [
            'board' => self::board($agent),
        ]);
    }

    /**
     * Live companies grouped by derived stage. Won/Lost only show when closed in the last 30 days.
     *
     * @return array<string, Collection<int, array{company: Company, value: string}>>
     */
    public static function board(?string $agent = null): array
    {
        // one collection per stage (array_fill_keys would share a single instance across all of them)
        $board = array_combine(Crm::STAGES, array_map(fn () => collect(), Crm::STAGES));
        $cut = today()->subDays(30);

        Company::visible()->live()
            ->when($agent, fn ($q) => $q->where('agent', $agent))
            ->with(['inquiries:id,company_id,service,status,budget_from,budget_to,closed_at', 'proposals:id,company_id,status,currency,total'])
            ->get()
            ->each(function (Company $c) use (&$board, $cut) {
                $stage = $c->stage();
                if (in_array($stage, ['Won', 'Lost'], true)) {
                    $closed = $c->inquiries->where('status', $stage)->max('closed_at') ?? $c->converted_at ?? $c->updated_at;
                    if ($closed && $closed->lt($cut)) {
                        return;
                    }
                }
                $board[$stage]->push(['company' => $c, 'value' => $c->dealValue($stage)]);
            });

        return $board;
    }
}
