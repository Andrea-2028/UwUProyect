<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    use HasFactory;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'name',
    ];

    protected $hidden = ['created_at', 'updated_at','pivot'];

    /**
     * Relación: una plataforma puede tener muchos juegos (N:N).
     */
    public function games()
    {
        return $this->belongsToMany(Game::class, 'game_platform', 'platform_id', 'game_id')
                    ->withTimestamps();
    }
}
