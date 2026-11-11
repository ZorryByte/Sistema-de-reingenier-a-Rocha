<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupLog extends Model
{
    protected $fillable = [
        'snapshot_id',
        'accion',
        'user_id',
        'ip_address',
        'contexto',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(BackupSnapshot::class, 'snapshot_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
