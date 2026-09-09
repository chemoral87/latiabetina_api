<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WhatsappMessageLog extends Model
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
        'resend_count',
        'original_log_id',
    ];

    protected $casts = [
        'success' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
