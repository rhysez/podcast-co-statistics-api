<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    use hasFactory;

    protected $fillable = [
      'event_id',
      'podcast_id',
      'episode_id',
      'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];
}
