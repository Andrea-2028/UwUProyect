<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * Campos que se pueden asignar de manera masiva.
     */
    protected $fillable = [
        'name',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Relación con usuarios (muchos a muchos).
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role', 'role_id', 'user_id')
                    ->withTimestamps();
    }
}
