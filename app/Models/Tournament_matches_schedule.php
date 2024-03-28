<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament_matches_schedule extends Model
{
    use HasFactory;
    protected $table = 'tournament_matches_schedule';
    protected $fillable = [
        'tournament_id',
        'schedule_date',
        'schedule_time',
        'schedule_breaks',
        'venue_availability',
    ];

    public function tournament_row()
    {
        return $this->belongsTo(Tournament::class,'tournament_id','id');
    }
}
