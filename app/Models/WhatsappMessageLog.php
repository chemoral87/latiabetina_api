<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'queue_name',
        'sender',
        'receiver',
        'body',
        'media_url',
        'success',
        'error_message',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'success' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
