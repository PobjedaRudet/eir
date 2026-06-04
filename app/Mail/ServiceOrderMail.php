<?php

namespace App\Mail;

use App\Models\ServiceOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ServiceOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ServiceOrder $serviceOrder,
    ) {}

    public function build(): static
    {
        $pdf = Pdf::loadView('pdf.service-order', ['serviceOrder' => $this->serviceOrder]);

        return $this
            ->subject("Servisni nalog #{$this->serviceOrder->id}")
            ->view('emails.service-order')
            ->with(['serviceOrder' => $this->serviceOrder])
            ->attachData(
                $pdf->output(),
                "servisni-nalog-{$this->serviceOrder->id}.pdf",
                ['mime' => 'application/pdf'],
            );
    }
}
