<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentType extends Model
{ 
    use HasFactory;
    protected $table = 'tournament_types';
    protected $fillable = [
    'name',
    'is_active'
];

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }
}
