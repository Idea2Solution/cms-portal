<?php

namespace App\Models\Concerns;

use App\Support\Crm;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sales agents see records of the leads assigned to them (and, when the model has an
 * `agent` column, records they own themselves). Admins see everything.
 */
trait VisibleThroughCompany
{
    protected static function ownerColumn(): ?string
    {
        return 'agent';
    }

    public function scopeVisible(Builder $q): Builder
    {
        if (Crm::isAdmin()) {
            return $q;
        }

        $col = static::ownerColumn();

        return $q->where(function ($w) use ($col) {
            $w->whereHas('company', fn ($c) => $c->where('agent', Crm::me()));
            if ($col) {
                $w->orWhere($this->qualifyColumn($col), Crm::me());
            }
        });
    }

    public function isVisible(): bool
    {
        $col = static::ownerColumn();

        return Crm::isAdmin()
            || $this->company?->agent === Crm::me()
            || ($col && $this->{$col} === Crm::me());
    }
}
