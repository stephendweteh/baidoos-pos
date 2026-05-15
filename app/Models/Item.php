<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = ['branch_id', 'name', 'price', 'type', 'stock_quantity', 'assign_staff', 'is_active'];

    protected $casts = [
        'stock_quantity' => 'integer',
        'assign_staff' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}
