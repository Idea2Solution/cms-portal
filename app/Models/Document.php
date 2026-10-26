<?php

namespace App\Models;

use App\Models\Concerns\VisibleThroughCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Expression of Interest (EoI) or Request for Quotation (RFQ), with attached files. */
class Document extends Model
{
    use VisibleThroughCompany;

    public const TYPES = ['EoI' => 'EOI', 'RFQ' => 'RFQ'];

    protected $fillable = [
        'code', 'type', 'company_id', 'inquiry_id', 'direction', 'date', 'ref', 'status', 'notes', 'files', 'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'files' => 'array',
    ];

    protected static function ownerColumn(): ?string
    {
        return 'created_by';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }
}
