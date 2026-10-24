<?php

namespace App\Models;

use App\Support\Crm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inquiry extends Model
{
    protected $fillable = [
        'code', 'company_id', 'date', 'source', 'agent', 'category', 'service', 'requirement',
        'budget_from', 'budget_to', 'start_date', 'priority', 'status', 'notes', 'closed_at', 'created_by',
    ];

    protected $casts = [
        'date' => 'datetime',
        'start_date' => 'date',
        'closed_at' => 'date',
        'budget_from' => 'decimal:2',
        'budget_to' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', Crm::OPEN_I);
    }

    /** Agents see inquiries they own or that belong to their leads. */
    public function scopeVisible(Builder $q): Builder
    {
        if (Crm::isAdmin()) {
            return $q;
        }

        return $q->where(fn ($w) => $w->where('agent', Crm::me())
            ->orWhereHas('company', fn ($c) => $c->where('agent', Crm::me())));
    }

    public function isVisible(): bool
    {
        return Crm::isAdmin() || $this->agent === Crm::me() || $this->company?->agent === Crm::me();
    }

    public function isOpen(): bool
    {
        return in_array($this->status, Crm::OPEN_I, true);
    }
}
