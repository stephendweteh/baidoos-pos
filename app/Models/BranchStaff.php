<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchStaff extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id', 'name', 'email', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'branch_staff_id');
    }
}