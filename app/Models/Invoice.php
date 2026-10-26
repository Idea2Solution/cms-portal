<?php

namespace App\Models;

use App\Models\Concerns\HasLineItems;
use App\Models\Concerns\VisibleThroughCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasLineItems, VisibleThroughCompany;

    public const OPEN_STATUSES = ['Issued', 'Partially Paid', 'Overdue'];

    protected $fillable = [
        'code', 'company_id', 'proposal_id', 'project', 'agent', 'state', 'date', 'due', 'currency',
        'items', 'discount', 'tax_pct', 'terms', 'notes', 'created_by',
    ];

    protected $casts = [
        'items' => 'array',
        'date' => 'date',
        'due' => 'date',
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

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Adds `paid_sum` (recorded payments only) so lists avoid one query per invoice. */
    public function scopeWithPaid(Builder $q): Builder
    {
        return $q->withSum(['payments as paid_sum' => fn ($p) => $p->where('status', 'Recorded')], 'amount');
    }

    /** Draft and Cancelled are set by hand; the rest follows from payments and the due date. */
    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('state', 'Issued');
    }

    public function paid(?int $exceptPaymentId = null): float
    {
        if ($exceptPaymentId === null && array_key_exists('paid_sum', $this->attributes)) {
            return round((float) $this->paid_sum, 2);
        }

        return round((float) $this->payments()
            ->where('status', 'Recorded')
            ->when($exceptPaymentId, fn ($q) => $q->whereKeyNot($exceptPaymentId))
            ->sum('amount'), 2);
    }

    public function balance(): float
    {
        return max(0, round((float) $this->total - $this->paid(), 2));
    }

    public function status(): string
    {
        if ($this->state !== 'Issued') {
            return $this->state;
        }
        $paid = $this->paid();
        if ((float) $this->total > 0 && $this->balance() <= 0.005) {
            return 'Paid';
        }
        if ($this->due && $this->due->isBefore(today())) {
            return 'Overdue';
        }

        return $paid > 0 ? 'Partially Paid' : 'Issued';
    }

    public function isOpen(): bool
    {
        return in_array($this->status(), self::OPEN_STATUSES, true);
    }
}
