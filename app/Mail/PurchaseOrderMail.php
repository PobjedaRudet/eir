<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly PurchaseOrder $purchaseOrder,
    ) {}

    public function build(): static
    {
        $pdf = Pdf::loadView('pdf.purchase-order', ['po' => $this->purchaseOrder]);

        return $this
            ->subject("Narudžbenica #{$this->purchaseOrder->id}")
            ->view('emails.purchase-order')
            ->with(['purchaseOrder' => $this->purchaseOrder])
            ->attachData(
                $pdf->output(),
                "narudzbenica-{$this->purchaseOrder->id}.pdf",
                ['mime' => 'application/pdf'],
            );
    }
}
