<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'description',
        'game_id',
    ];

    /**
     * Relación: una notificación pertenece a un juego.
     */
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * Relación: una notificación puede estar asignada a muchos usuarios (N:N).
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'notification_user', 'notification_id', 'user_id')
                    ->withTimestamps()
                    ->withPivot('status');
    }
}
