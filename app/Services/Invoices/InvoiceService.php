<?php

declare(strict_types=1);

namespace App\Services\Invoices;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function generateForPayment(Payment $payment): Payment
    {
        $payment->loadMissing(['booking.field', 'booking.user']);

        if ($payment->status !== Payment::STATUS_SUCCESS) {
            throw ValidationException::withMessages([
                'payment' => ['Invoice can only be generated for successful payments.'],
            ]);
        }

        if ($payment->invoice_pdf_path !== null && Storage::disk('local')->exists($payment->invoice_pdf_path)) {
            return $payment;
        }

        $invoiceNumber = $payment->invoice_number ?: $this->generateInvoiceNumber($payment);
        $path = sprintf('invoices/%s.pdf', $invoiceNumber);

        $pdf = Pdf::loadView('invoices.payment', [
            'payment' => $payment,
            'booking' => $payment->booking,
            'field' => $payment->booking->field,
            'customerName' => $payment->booking->customer_name ?: $payment->booking->user?->name ?: 'Customer',
            'customerEmail' => $payment->booking->customer_email ?: $payment->booking->user?->email,
            'customerContact' => $payment->booking->customer_contact,
            'invoiceNumber' => $invoiceNumber,
        ])->setPaper('a4');

        Storage::disk('local')->put($path, $pdf->output());

        $payment->forceFill([
            'invoice_number' => $invoiceNumber,
            'invoice_pdf_path' => $path,
            'invoice_generated_at' => now(),
        ])->save();

        return $payment->fresh(['booking.field', 'booking.user']);
    }

    private function generateInvoiceNumber(Payment $payment): string
    {
        $year = now()->format('Y');
        $sequence = str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT);

        return sprintf('INV-%s-%s', $year, $sequence);
    }
}
