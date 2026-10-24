<?php

namespace App\Models;

use App\Support\Crm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Followup extends Model
{
    protected $fillable = [
        'code', 'company_id', 'inquiry_id', 'agent', 'due', 'method', 'purpose', 'status', 'outcome', 'completed_at',
    ];

    protected $casts = [
        'due' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'Pending');
    }

    /** Agents see follow-ups they own or that belong to their leads. */
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

    public function isOverdue(): bool
    {
        return $this->status === 'Pending' && $this->due->isPast();
    }
}
