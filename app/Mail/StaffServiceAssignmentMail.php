<?php

namespace App\Mail;

use App\Models\BranchStaff;
use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class StaffServiceAssignmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public Sale $sale;
    public BranchStaff $staff;
    public Collection $assignedItems;

    public function __construct(Sale $sale, BranchStaff $staff, Collection $assignedItems)
    {
        $this->sale = $sale;
        $this->staff = $staff;
        $this->assignedItems = $assignedItems;
    }

    public function build()
    {
        return $this->subject('Service Assignment - Sale #' . $this->sale->id)
            ->view('emails.staff_service_assignment');
    }
}
