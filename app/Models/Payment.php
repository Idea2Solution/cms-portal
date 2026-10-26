<?php

namespace App\Models;

use App\Models\Concerns\VisibleThroughCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use VisibleThroughCompany;

    protected $fillable = [
        'code', 'company_id', 'invoice_id', 'date', 'method', 'amount', 'reference', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function ownerColumn(): ?string
    {
        return null;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
