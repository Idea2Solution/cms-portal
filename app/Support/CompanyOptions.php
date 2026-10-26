<?php

namespace App\Support;

use App\Models\Company;

/** Companies the user can see, with what the sales-document forms need for their dependent dropdowns. */
class CompanyOptions
{
    public static function forForms(): array
    {
        return Company::visible()->orderBy('name')
            ->with([
                'inquiries:id,company_id,code,service',
                'proposals:id,company_id,inquiry_id,code,status,currency,total,tax_pct,discount,items,terms',
                'proposals.inquiry:id,service',
            ])
            ->get(['id', 'code', 'name', 'agent'])
            ->mapWithKeys(fn (Company $c) => [$c->id => [
                'label' => "{$c->name} · {$c->code}",
                'agent' => $c->agent,
                'inquiries' => $c->inquiries->map(fn ($i) => ['id' => $i->id, 'label' => "{$i->code} · {$i->service}"])->values(),
                'proposals' => $c->proposals->map(fn ($p) => [
                    'id' => $p->id,
                    'label' => "{$p->code} · {$p->status} · ".Crm::money($p->total, $p->currency),
                    'currency' => $p->currency,
                    'tax_pct' => (float) $p->tax_pct,
                    'discount' => (float) $p->discount,
                    'items' => $p->items,
                    'terms' => $p->terms,
                    'project' => $p->inquiry?->service,
                ])->values(),
            ]])
            ->all();
    }

    /** [id => label] for a plain select. */
    public static function labels(array $options): array
    {
        return array_map(fn ($c) => $c['label'], $options);
    }
}
