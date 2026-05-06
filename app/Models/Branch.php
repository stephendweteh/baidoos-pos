<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'business_type_id', 'address', 'phone', 'is_active'];

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function dayClosings()
    {
        return $this->hasMany(DayClosing::class);
    }

    public function todayClosing()
    {
        return $this->hasOne(DayClosing::class)->whereDate('closing_date', today());
    }
}
