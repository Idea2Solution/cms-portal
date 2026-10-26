<?php

namespace App\Models\Concerns;

/**
 * Line items [{service, desc, qty, price}] with stored subtotal / tax / total,
 * recalculated on every save. Discount is an amount, capped at the subtotal.
 */
trait HasLineItems
{
    public static function bootHasLineItems(): void
    {
        static::saving(function ($model) {
            $t = static::computeTotals($model->items ?? [], (float) $model->discount, (float) $model->tax_pct);
            $model->subtotal = $t['subtotal'];
            $model->discount = $t['discount'];
            $model->tax = $t['tax'];
            $model->total = $t['total'];
        });
    }

    public static function computeTotals(array $items, float $discount, float $taxPct): array
    {
        $sub = round(collect($items)->sum(fn ($i) => (float) ($i['qty'] ?? 0) * (float) ($i['price'] ?? 0)), 2);
        $disc = round(min(max($discount, 0), $sub), 2);
        $tax = round(($sub - $disc) * $taxPct / 100, 2);

        return ['subtotal' => $sub, 'discount' => $disc, 'tax' => $tax, 'total' => round($sub - $disc + $tax, 2)];
    }
}
