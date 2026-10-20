<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class LogEntry extends Model
{
    protected $table = 'logs';

    protected $fillable = ['level', 'channel', 'message', 'context'];

    protected $casts = [
        'context' => 'array',
    ];
}
