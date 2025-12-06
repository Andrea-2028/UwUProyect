<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'title',
        'description',
        'last_update',
        'release_date',
        'status',
        'image',
        'developer_id',
        'category_id',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Relación con el desarrollador (N:1).
     */
    public function developer()
    {
        return $this->belongsTo(Developer::class, 'developer_id');
    }

    /**
     * Relación con la categoría (N:1).
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Relación con las plataformas (N:N).
     */
    public function platforms()
    {
        return $this->belongsToMany(Platform::class, 'game_platform', 'game_id', 'platform_id')
                    ->withTimestamps();
    }

    /**
     * Relación con usuarios (favoritos N:N).
     */
    public function usersFavorited()
    {
        return $this->belongsToMany(User::class, 'favorite_games', 'game_id', 'user_id')
                    ->withTimestamps()
                    ->withPivot('status');
    }

    /**
     * Relación con notificaciones.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'game_id');
    }

    //Relacion con favoritos
    public function favorites()
    {
        return $this->hasMany(FavoriteGame::class);
    }

}
