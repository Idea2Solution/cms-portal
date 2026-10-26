<?php

namespace App\Models;

use App\Models\Concerns\HasLineItems;
use App\Models\Concerns\VisibleThroughCompany;
use App\Support\Crm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proposal extends Model
{
    use HasLineItems, VisibleThroughCompany;

    protected $fillable = [
        'code', 'company_id', 'inquiry_id', 'agent', 'status', 'date', 'valid_until', 'currency',
        'items', 'discount', 'tax_pct', 'terms', 'notes', 'created_by',
    ];

    protected $casts = [
        'items' => 'array',
        'date' => 'date',
        'valid_until' => 'date',
        'discount' => 'decimal:2',
        'tax_pct' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', Crm::OPEN_P);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, Crm::OPEN_P, true);
    }
}
