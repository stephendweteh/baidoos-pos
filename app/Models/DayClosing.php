<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id', 'user_id', 'closing_date',
        'opening_cash', 'total_sales',
        'total_cash_sales', 'total_momo_sales',
        'transaction_count', 'cash_counted', 'cash_variance', 'notes',
    ];

    protected $casts = [
        'closing_date' => 'date',
    ];

    // ─── Relationships ────────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
