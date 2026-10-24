<?php

namespace App\Models;

use App\Support\Crm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'code', 'type', 'customer_no', 'name', 'category', 'website', 'country', 'city', 'address', 'description',
        'contact', 'designation', 'phone', 'phone2', 'email', 'whatsapp', 'preferred', 'socials', 'source',
        'source_url', 'agent', 'priority', 'status', 'notes', 'last_contacted', 'converted_at', 'created_by',
    ];

    protected $casts = [
        'socials' => 'array',
        'last_contacted' => 'date',
        'converted_at' => 'date',
    ];

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class);
    }

    public function remarks(): HasMany
    {
        return $this->hasMany(Remark::class);
    }

    /** With the customers module off, customers are listed with the leads so nothing disappears. */
    public function scopeLeads(Builder $q): Builder
    {
        return Crm::customersOn() ? $q->where('type', 'Prospect') : $q;
    }

    public function scopeCustomers(Builder $q): Builder
    {
        return $q->where('type', 'Customer');
    }

    /** Sales agents only see the leads/customers assigned to them; admins see everything. */
    public function scopeVisible(Builder $q): Builder
    {
        return Crm::isAdmin() ? $q : $q->where('companies.agent', Crm::me());
    }

    public function isVisible(): bool
    {
        return Crm::isAdmin() || $this->agent === Crm::me();
    }

    public function scopeLive(Builder $q): Builder
    {
        return $q->whereNotIn('status', ['Archived', 'Unqualified']);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(function (Builder $w) use ($term) {
            foreach (['code', 'customer_no', 'name', 'contact', 'phone', 'phone2', 'whatsapp', 'email', 'category', 'website'] as $col) {
                $w->orWhere($col, 'like', "%{$term}%");
            }
        });
    }

    public function isCustomer(): bool
    {
        return Crm::customersOn() && $this->type === 'Customer';
    }

    public function nextFollowup(): ?Followup
    {
        return $this->followups->where('status', 'Pending')->sortBy('due')->first();
    }

    /** Add an entry to the remarks timeline. */
    public function log(string $text, string $kind = 'System'): Remark
    {
        return $this->remarks()->create(['kind' => $kind, 'text' => $text, 'by' => Crm::me()]);
    }

    /** Any real contact (call, inquiry, completed follow-up) moves a lead out of "Not Contacted". */
    public function touchContacted(): void
    {
        $this->last_contacted = today();
        if ($this->status === 'Not Contacted') {
            $this->status = 'Contacted';
            $this->log('Status changed: Not Contacted → Contacted');
        }
        $this->save();
    }

    public function convertToCustomer(): bool
    {
        if (! Crm::customersOn() || $this->isCustomer()) {
            return false;
        }

        $this->type = 'Customer';
        $this->customer_no = Crm::nextCode(self::class, 'C', 'customer_no');
        $this->converted_at = today();
        if (in_array($this->status, ['Not Contacted', 'Contacted'], true)) {
            $this->status = 'Qualified';
        }
        $this->save();
        $this->log('Converted to customer '.$this->customer_no);

        return true;
    }

    /** Pipeline stage derived from status and inquiries, so nobody has to maintain it by hand. */
    public function stage(): string
    {
        $inqs = $this->inquiries;
        $open = $inqs->filter->isOpen();

        if ($open->isNotEmpty()) {
            if ($open->contains('status', 'Negotiation')) {
                return 'Negotiation';
            }
            if ($open->whereIn('status', ['Proposal Required', 'Proposal Sent'])->isNotEmpty()) {
                return 'Proposal';
            }

            return 'Inquiry';
        }
        if ($inqs->contains('status', 'Won') || $this->isCustomer()) {
            return 'Won';
        }
        if ($inqs->isNotEmpty() && $inqs->every(fn ($i) => $i->status === 'Lost')) {
            return 'Lost';
        }

        return match ($this->status) {
            'Qualified' => 'Qualified',
            'Contacted' => 'Contacted',
            default => 'New Lead',
        };
    }
}
