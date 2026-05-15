<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'channel',
        'total_recipients',
        'sent_count',
        'failed_count',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Relationship: User who created this broadcast.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope: Get drafts.
     */
    public function scopeDrafts($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope: Get sent/completed broadcasts.
     */
    public function scopeSent($query)
    {
        return $query->whereIn('status', ['completed', 'failed']);
    }
}
