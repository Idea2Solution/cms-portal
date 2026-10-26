<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Followup;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Proposal;
use App\Models\Setting;
use App\Models\User;
use App\Support\Crm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            's' => Crm::settings(),
            'users' => User::orderByDesc('active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'currency' => ['required', Rule::in(Crm::CURS)],
            'taxPct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'company.name' => ['required', 'string', 'max:255'],
            'company.tagline' => ['nullable', 'string', 'max:255'],
            'company.email' => ['nullable', 'email', 'max:255'],
            'company.phone' => ['nullable', 'string', 'max:50'],
            'company.website' => ['nullable', 'string', 'max:255'],
            'company.address' => ['nullable', 'string', 'max:500'],
        ]);

        $this->put('currency', $v['currency']);
        $this->put('taxPct', (float) ($v['taxPct'] ?? 0));
        $this->put('terms', $v['terms'] ?? '');
        $this->put('company', array_map(fn ($x) => $x ?? '', $v['company']));

        return back()->with('ok', 'Settings saved');
    }

    /** Add a value to one of the editable lists: categories, a service category or a sub-service. */
    public function addItem(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'k' => ['required', Rule::in(['categories', 'cat', 'svc'])],
            'c' => ['nullable', 'string'],
            'value' => ['required', 'string', 'max:100'],
        ]);
        $val = trim($v['value']);

        if ($v['k'] === 'cat' || $v['k'] === 'svc') {
            $services = Crm::setting('services');
            if ($v['k'] === 'cat') {
                $services[$val] ??= ['General'];
            } elseif (isset($services[$v['c']]) && ! in_array($val, $services[$v['c']], true)) {
                $services[$v['c']][] = $val;
            }
            $this->put('services', $services);
        } else {
            $list = Crm::setting($v['k']);
            if (! in_array($val, $list, true)) {
                $list[] = $val;
            }
            $this->put($v['k'], $list);
        }

        return back()->with('ok', 'Added');
    }

    public function removeItem(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'k' => ['required', Rule::in(['categories', 'cat', 'svc'])],
            'c' => ['nullable', 'string'],
            'value' => ['required', 'string'],
        ]);

        if ($v['k'] === 'cat' || $v['k'] === 'svc') {
            $services = Crm::setting('services');
            if ($v['k'] === 'cat' && count($services) > 1) {
                unset($services[$v['value']]);
            } elseif ($v['k'] === 'svc' && count($services[$v['c']] ?? []) > 1) {
                $services[$v['c']] = array_values(array_diff($services[$v['c']], [$v['value']]));
            } else {
                return back()->with('err', 'At least one entry has to stay');
            }
            $this->put('services', $services);
        } else {
            $this->put($v['k'], array_values(array_diff(Crm::setting($v['k']), [$v['value']])));
        }

        return back()->with('ok', 'Removed');
    }

    public function export(string $type): StreamedResponse
    {
        [$query, $cols] = match ($type) {
            'leads' => [Company::leads(), ['code', 'name', 'category', 'contact', 'designation', 'phone', 'phone2', 'whatsapp', 'email', 'website', 'city', 'country', 'source', 'agent', 'priority', 'status', 'last_contacted', 'created_at']],
            'customers' => [Company::customers(), ['customer_no', 'code', 'name', 'category', 'contact', 'phone', 'whatsapp', 'email', 'website', 'city', 'country', 'agent', 'status', 'converted_at']],
            'inquiries' => [Inquiry::with('company:id,name'), ['code', 'company', 'date', 'source', 'agent', 'category', 'service', 'requirement', 'budget_from', 'budget_to', 'start_date', 'priority', 'status', 'closed_at']],
            'followups' => [Followup::with('company:id,name'), ['code', 'company', 'due', 'method', 'purpose', 'agent', 'status', 'outcome', 'completed_at']],
            'proposals' => [Proposal::with('company:id,name'), ['code', 'company', 'date', 'valid_until', 'agent', 'status', 'currency', 'subtotal', 'discount', 'tax', 'total', 'lines']],
            'invoices' => [Invoice::withPaid()->with('company:id,name'), ['code', 'company', 'date', 'due', 'project', 'agent', 'state', 'invoice_status', 'currency', 'total', 'paid', 'balance', 'lines']],
            'payments' => [Payment::with(['company:id,name', 'invoice:id,code,currency']), ['code', 'company', 'invoice', 'date', 'method', 'amount', 'currency', 'reference', 'status']],
        };

        return response()->streamDownload(function () use ($query, $cols) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8
            fputcsv($out, $cols);
            $query->orderBy('id')->chunk(500, function ($rows) use ($out, $cols) {
                foreach ($rows as $r) {
                    fputcsv($out, array_map(fn ($c) => match ($c) {
                        'company' => $r->company?->name,
                        'invoice' => $r->invoice?->code,
                        'currency' => $r->currency ?? $r->invoice?->currency,
                        'invoice_status' => $r->status(),
                        'paid' => $r->paid(),
                        'balance' => $r->balance(),
                        'lines' => collect($r->items)->map(fn ($i) => "{$i['service']} x{$i['qty']} @{$i['price']}")->join(' | '),
                        default => $r->{$c} instanceof \DateTimeInterface
                            ? $r->{$c}->format($r->{$c}->format('H:i') === '00:00' ? 'Y-m-d' : 'Y-m-d H:i')
                            : (string) $r->{$c},
                    }, $cols));
                }
            });
            fclose($out);
        }, "{$type}-".today()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function put(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        Crm::forget();
    }
}
