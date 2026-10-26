<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Proposal;
use App\Support\Crm;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Proposal, invoice and payment-receipt PDFs in the company's branding. */
class PdfController extends Controller
{
    public function proposal(Proposal $proposal): Response
    {
        abort_unless($proposal->isVisible(), 403);

        return $this->render('proposal', $proposal->load('company'), [
            'title' => 'PROPOSAL',
            'to' => 'PREPARED FOR',
            'meta' => [
                'Proposal date' => $proposal->date?->format('j M Y'),
                'Valid until' => $proposal->valid_until?->format('j M Y'),
                'Prepared by' => $proposal->agent,
                'Currency' => $proposal->currency,
            ],
        ]);
    }

    public function invoice(Invoice $invoice): Response
    {
        abort_unless($invoice->isVisible(), 403);
        $invoice->load(['company', 'proposal:id,code']);

        return $this->render('invoice', $invoice, [
            'title' => 'INVOICE',
            'to' => 'BILL TO',
            'meta' => [
                'Invoice date' => $invoice->date?->format('j M Y'),
                'Due date' => $invoice->due?->format('j M Y'),
                'Project' => $invoice->project,
                'Proposal ref' => $invoice->proposal?->code,
                'Status' => $invoice->status(),
            ],
        ]);
    }

    public function receipt(Payment $payment): Response
    {
        abort_unless($payment->isVisible(), 403);
        $payment->load(['company', 'invoice']);

        return $this->render('receipt', $payment, [
            'title' => 'PAYMENT RECEIPT',
            'to' => 'RECEIVED FROM',
            'meta' => [
                'Receipt date' => $payment->date?->format('j M Y'),
                'Method' => $payment->method,
                'Reference' => $payment->reference,
                'Invoice' => $payment->invoice->code,
            ],
        ]);
    }

    private function render(string $kind, $record, array $data): Response
    {
        $pdf = Pdf::loadView('pdf.document', $data + [
            'kind' => $kind,
            'rec' => $record,
            'co' => Crm::setting('company'),
            'currency' => $kind === 'receipt' ? $record->invoice->currency : $record->currency,
        ])->setPaper('a4')->setOption('isFontSubsettingEnabled', true); // embed only used glyphs (~40 KB instead of ~900 KB)

        $name = Str::slug($record->code.' '.$record->company->name).'.pdf';

        return request()->boolean('download') ? $pdf->download($name) : $pdf->stream($name);
    }
}
