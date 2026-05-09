<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id', 'item_id', 'branch_staff_id', 'item_name', 'item_price', 'quantity', 'subtotal',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function staff()
    {
        return $this->belongsTo(BranchStaff::class, 'branch_staff_id');
    }
}
