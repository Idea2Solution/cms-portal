<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentRequest;
use App\Models\Company;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Proposal;
use App\Support\CompanyOptions;
use App\Support\Crm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /** EoIs, RFQs, proposals, invoices and payments in one list. */
    public function index(Request $request): View
    {
        $type = in_array($request->query('type'), Crm::DOC_TYPES, true) ? $request->query('type') : '';
        $q = Str::lower(trim((string) $request->query('q')));

        $all = $this->rows();
        $counts = $all->countBy('type');

        $rows = $all
            ->when($type, fn ($c) => $c->where('type', $type))
            ->when($q, fn ($c) => $c->filter(fn ($r) => Str::contains(Str::lower($r['ref'].' '.$r['company'].' '.$r['note']), $q)))
            ->sortByDesc(fn ($r) => $r['date']?->format('Y-m-d').$r['ref'])
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator($rows->forPage($page, 20)->values(), $rows->count(), 20, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('documents.index', [
            'docs' => $paginator,
            'type' => $type,
            'counts' => $counts,
            'total' => $all->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $type = array_key_exists($request->query('type'), Document::TYPES) ? $request->query('type') : 'EoI';
        $company = Company::visible()->find($request->query('company'));

        return view('documents.form', [
            'document' => new Document([
                'type' => $type,
                'company_id' => $company?->id,
                'direction' => 'Received',
                'date' => today(),
                'status' => 'Open',
                'files' => [],
            ]),
            'companies' => CompanyOptions::forForms(),
        ]);
    }

    public function store(DocumentRequest $request): RedirectResponse
    {
        $data = collect($request->validated())->except(['files', 'remove_files'])->all();
        $doc = Document::create($data + [
            'code' => Crm::nextCode(Document::class, Document::TYPES[$data['type']]),
            'files' => $this->storeFiles($request),
            'created_by' => Crm::me(),
        ]);
        $doc->company->log("{$doc->type} {$doc->code} ".Str::lower($doc->direction).($doc->ref ? " (ref {$doc->ref})" : ''));

        return redirect()->route('documents.edit', $doc)->with('ok', "{$doc->type} saved");
    }

    public function edit(Document $document): View
    {
        $this->guard($document);

        return view('documents.form', [
            'document' => $document,
            'companies' => CompanyOptions::forForms(),
        ]);
    }

    public function update(DocumentRequest $request, Document $document): RedirectResponse
    {
        $this->guard($document);

        $remove = $request->input('remove_files', []);
        $files = collect($document->files ?? [])->reject(function ($f) use ($remove) {
            if (in_array($f['path'], $remove, true)) {
                Storage::disk('local')->delete($f['path']);

                return true;
            }

            return false;
        })->values()->all();

        $document->update(collect($request->validated())->except(['files', 'remove_files'])->all() + [
            'files' => array_merge($files, $this->storeFiles($request)),
        ]);

        return redirect()->route('documents.edit', $document)->with('ok', "{$document->type} saved");
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->guard($document);

        foreach ($document->files ?? [] as $f) {
            Storage::disk('local')->delete($f['path']);
        }
        $company = $document->company;
        $document->delete();
        $company->log("{$document->type} {$document->code} deleted");

        return redirect()->route('documents.index')->with('ok', 'Deleted');
    }

    public function download(Document $document, int $index): StreamedResponse
    {
        $this->guard($document);
        $file = ($document->files ?? [])[$index] ?? abort(404);
        abort_unless(Storage::disk('local')->exists($file['path']), 404, 'The file is missing from storage.');

        return Storage::disk('local')->download($file['path'], $file['name']);
    }

    /** Uploaded files are kept on the private disk and only served through download(). */
    private function storeFiles(DocumentRequest $request): array
    {
        return collect($request->file('files', []))
            ->map(fn ($file) => [
                'path' => $file->store('documents', 'local'),
                'name' => $file->getClientOriginalName(),
            ])
            ->all();
    }

    private function rows(): Collection
    {
        $docs = Document::visible()->with('company:id,name')->get()->map(fn (Document $d) => [
            'type' => $d->type, 'ref' => $d->code, 'company' => $d->company->name, 'company_id' => $d->company_id,
            'date' => $d->date, 'amount' => '', 'status' => $d->status, 'note' => $d->ref ? "Ref {$d->ref}" : '',
            'url' => route('documents.edit', $d), 'pdf' => null,
        ]);

        $proposals = Proposal::visible()->with('company:id,name')->get()->map(fn (Proposal $p) => [
            'type' => 'Proposal', 'ref' => $p->code, 'company' => $p->company->name, 'company_id' => $p->company_id,
            'date' => $p->date, 'amount' => Crm::money($p->total, $p->currency), 'status' => $p->status, 'note' => '',
            'url' => route('proposals.edit', $p), 'pdf' => route('pdf.proposal', $p),
        ]);

        $invoices = Invoice::visible()->withPaid()->with('company:id,name')->get()->map(function (Invoice $i) {
            $status = $i->status();

            return [
                'type' => 'Invoice', 'ref' => $i->code, 'company' => $i->company->name, 'company_id' => $i->company_id,
                'date' => $i->date, 'amount' => Crm::money($i->total, $i->currency), 'status' => $status,
                'note' => $i->balance() > 0 && ! in_array($status, ['Draft', 'Cancelled'], true) ? 'Balance '.Crm::money($i->balance(), $i->currency) : '',
                'url' => route('invoices.edit', $i), 'pdf' => route('pdf.invoice', $i),
            ];
        });

        $payments = Payment::visible()->with(['company:id,name', 'invoice:id,code,currency'])->get()->map(fn (Payment $p) => [
            'type' => 'Payment', 'ref' => $p->code, 'company' => $p->company->name, 'company_id' => $p->company_id,
            'date' => $p->date, 'amount' => Crm::money($p->amount, $p->invoice->currency), 'status' => $p->status,
            'note' => $p->invoice->code, 'url' => route('payments.edit', $p), 'pdf' => route('pdf.receipt', $p),
        ]);

        return $docs->concat($proposals)->concat($invoices)->concat($payments);
    }

    private function guard(Document $document): void
    {
        abort_unless($document->isVisible(), 403, 'This document belongs to another agent.');
    }
}
