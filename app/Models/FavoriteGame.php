<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteGame extends Model
{
    protected $fillable = [
        'user_id',
        'game_id',
        'status'
    ];

    // Relación: favorito pertenece a un juego
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    // Relación: favorito pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
