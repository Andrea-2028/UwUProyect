<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Relación: una categoría puede tener muchos juegos.
     */
    public function games()
    {
        return $this->hasMany(Game::class, 'category_id');
    }
}
