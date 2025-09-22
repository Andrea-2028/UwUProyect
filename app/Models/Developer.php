<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Developer extends Model
{
    use HasFactory;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'name',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Relación: un desarrollador puede tener muchos juegos.
     */
    public function games()
    {
        return $this->hasMany(Game::class, 'developer_id');
    }
}
