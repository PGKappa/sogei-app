<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RTP (market => valore su 1.000.000) con cui un evento è stato inviato a Isibet.
 * Un array vuoto significa "default della libreria".
 */
class EventRtp extends Model
{
    protected $table = 'event_rtps';

    protected $fillable = ['event_id', 'game_id', 'rtp'];

    public function getRtp(): array
    {
        $decoded = json_decode((string) $this->rtp, true);
        return is_array($decoded) ? $decoded : [];
    }
}
