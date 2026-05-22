<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id', 'user_id', 'day_closing_id', 'sale_date',
        'subtotal', 'discount', 'total', 'payment_method',
        'payment_status', 'payment_reference', 'momo_status', 'payer_msisdn', 'momo_ref',
        'customer_name', 'customer_phone', 'customer_email', 'notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
    ];

    // ─── Relationships ────────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dayClosing()
    {
        return $this->belongsTo(DayClosing::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
