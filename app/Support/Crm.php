<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;

/**
 * Fixed option lists and small helpers shared by controllers and views.
 */
class Crm
{
    public const PRIOS = ['Hot', 'Warm', 'Cold'];
    public const LSTATUS = ['Not Contacted', 'Contacted', 'Qualified', 'Unqualified', 'Archived'];
    public const SOURCES = ['Website', 'WhatsApp', 'Phone', 'Email', 'Facebook', 'Instagram', 'LinkedIn', 'Referral', 'Sales Prospecting', 'Campaign', 'Other'];
    public const ISTATUS = ['New', 'In Progress', 'Proposal Required', 'Proposal Sent', 'Negotiation', 'Won', 'Lost', 'On Hold'];
    public const OPEN_I = ['New', 'In Progress', 'Proposal Required', 'Proposal Sent', 'Negotiation'];
    public const METHODS = ['Phone', 'WhatsApp', 'Email', 'Meeting', 'Video Call', 'Other'];
    public const FSTATUS = ['Pending', 'Completed', 'Rescheduled', 'Cancelled'];
    public const CURS = ['GBP', 'AED', 'USD', 'PKR', 'EUR'];
    public const SOCIALS = ['Facebook', 'Instagram', 'LinkedIn', 'TikTok', 'YouTube', 'X / Twitter', 'Other'];
    public const REMARK_KINDS = ['Call', 'WhatsApp', 'Email', 'Meeting', 'Note'];
    public const PSTATUS = ['Draft', 'Sent', 'Under Discussion', 'Negotiation', 'Accepted', 'Rejected', 'Expired', 'Cancelled'];
    public const OPEN_P = ['Draft', 'Sent', 'Under Discussion', 'Negotiation'];
    public const ISTATES = ['Draft', 'Issued', 'Cancelled'];
    public const PAYM = ['Bank Transfer', 'Cash', 'Card', 'Online Payment', 'Cheque', 'Other'];
    public const PAYSTATUS = ['Recorded', 'Reversed', 'Cancelled'];
    public const DOC_TYPES = ['EoI', 'RFQ', 'Proposal', 'Invoice', 'Payment'];
    public const DSTATUS = ['Open', 'Responded', 'Closed'];
    public const STAGES = ['New Lead', 'Contacted', 'Qualified', 'Inquiry', 'Proposal', 'Negotiation', 'Won', 'Lost'];

    public const DEFAULTS = [
        'currency' => 'GBP',
        'taxPct' => 0,
        'terms' => '',
        'company' => [
            'name' => 'idea2solution',
            'tagline' => 'Conductors of Creativity',
            'address' => 'Highbank House, Exchange Street, Stockport, SK3 0ET, United Kingdom',
            'email' => 'info@dorange.com',
            'phone' => '+44 7376 075378',
            'website' => 'dorange.co.uk',
        ],
        'categories' => ['Real Estate', 'Automotive', 'Food & Beverage', 'Retail & E-commerce', 'Healthcare', 'Beauty & Wellness', 'Education', 'Finance & Accounting', 'HR & Recruitment', 'Manufacturing', 'Hospitality', 'Technology', 'Other'],
        'services' => [
            'Digital Marketing' => ['SEO', 'Social Media Management', 'Google Ads', 'Meta Ads', 'Content Marketing', 'Local SEO'],
            'Website' => ['Corporate Website', 'Landing Page', 'E-commerce', 'Redesign', 'Maintenance'],
            'Creative' => ['Graphic Design', 'Branding', 'Video', 'Motion Graphics'],
            'Software / IT' => ['Custom Software', 'CRM', 'Mobile App', 'API Integration', 'Maintenance'],
            'AI / Automation' => ['AI Agent', 'Chatbot', 'Workflow Automation', 'WhatsApp Automation'],
            'Other' => ['Other'],
        ],
    ];

    private const TONE = [
        'good' => ['Qualified', 'Won', 'Completed', 'Customer', 'Accepted', 'Paid', 'Recorded', 'Responded'],
        'warn' => ['Not Contacted', 'In Progress', 'Proposal Required', 'Pending', 'Rescheduled', 'Partially Paid', 'Under Discussion'],
        'info' => ['Contacted', 'New', 'Proposal Sent', 'Sent', 'Issued', 'Open', 'Received'],
        'brand' => ['Negotiation'],
        'bad' => ['Lost', 'Overdue', 'Cancelled', 'Rejected', 'Reversed'],
    ];

    private static ?array $settings = null;
    private static ?array $agents = null;

    public static function settings(): array
    {
        if (self::$settings === null) {
            $stored = Setting::query()->pluck('value', 'key')->all();
            $merged = array_merge(self::DEFAULTS, $stored);
            $merged['company'] = array_merge(self::DEFAULTS['company'], $stored['company'] ?? []);
            self::$settings = $merged;
        }

        return self::$settings;
    }

    public static function setting(string $key): mixed
    {
        return self::settings()[$key] ?? null;
    }

    public static function forget(): void
    {
        self::$settings = null;
        self::$agents = null;
    }

    /** Names of all active users – every user (admin or sales agent) can own leads. */
    public static function agents(): array
    {
        return self::$agents ??= User::where('active', true)->orderBy('name')->pluck('name')->all() ?: ['Unassigned'];
    }

    /** The logged-in user's name; records store the owner by name in their `agent` column. */
    public static function me(): string
    {
        return auth()->user()?->name ?? 'System';
    }

    public static function customersOn(): bool
    {
        return (bool) config('crm.customers');
    }

    public static function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    /** Agents the current user may assign records to: admins pick anyone, agents only themselves. */
    public static function assignable(): array
    {
        return self::isAdmin() ? self::agents() : [self::me()];
    }

    /** Validation rule: the company exists and the current user may work on it. */
    public static function visibleCompanyRule(): \Illuminate\Validation\Rules\Exists
    {
        return \Illuminate\Validation\Rule::exists('companies', 'id')
            ->where(fn ($q) => self::isAdmin() ? $q : $q->where('agent', self::me()));
    }

    public static function tone(?string $status): string
    {
        foreach (self::TONE as $tone => $list) {
            if (in_array($status, $list, true)) {
                return $tone;
            }
        }

        return 'mute';
    }

    /** Next sequential code such as P-00012, based on the highest existing one. */
    public static function nextCode(string $modelClass, string $prefix, string $column = 'code'): string
    {
        $max = $modelClass::query()
            ->where($column, 'like', $prefix.'-%')
            ->pluck($column)
            ->map(fn ($c) => (int) substr($c, strlen($prefix) + 1))
            ->max() ?? 0;

        return $prefix.'-'.str_pad($max + 1, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Sum amounts that may be in different currencies, e.g. "£1,200.00 · AED 500.00".
     *
     * @param  iterable<array{0: ?string, 1: float|int|string}>  $pairs  [currency, amount]
     */
    public static function sumByCurrency(iterable $pairs): string
    {
        $totals = [];
        foreach ($pairs as [$cur, $amount]) {
            $cur = $cur ?: self::setting('currency');
            $totals[$cur] = ($totals[$cur] ?? 0) + (float) $amount;
        }
        if (! $totals) {
            return self::money(0);
        }

        return collect($totals)->map(fn ($v, $c) => self::money($v, $c))->join(' · ');
    }

    public static function money(float|int|string|null $amount, ?string $currency = null): string
    {
        $currency = $currency ?: self::setting('currency');
        $symbols = ['GBP' => '£', 'USD' => '$', 'EUR' => '€', 'AED' => 'AED ', 'PKR' => 'PKR '];

        return ($symbols[$currency] ?? $currency.' ').number_format((float) $amount, 2);
    }
}
