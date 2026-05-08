<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OwnerSaleAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public Sale $sale;

    public function __construct(Sale $sale)
    {
        $this->sale = $sale;
    }

    public function build()
    {
        return $this->subject('New Sale Alert – #' . $this->sale->id . ' | ' . $this->sale->branch->name)
            ->view('emails.owner_sale_alert');
    }
}
