<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // Para login con Auth
use Illuminate\Notifications\Notifiable;

class AtUser extends Authenticatable
{
    use HasFactory, Notifiable;

    // Nombre exacto de la tabla
    protected $table = 'at_users';

    // Clave primaria personalizada
    protected $primaryKey = 'uid';

    // Si la clave primaria no es autoincremental, quitar el auto incremento
    // protected $incrementing = false;

    // Si la clave primaria no es un entero
    // protected $keyType = 'string';

    // Campos asignables masivamente
    protected $fillable = [
        'username',
        'email',
        'password',
        'status',
        'asana_id',
        'asana_access_token',
        'asana_refresh_token',
        'date_creation',
        'date_update',
    ];

    // 🔹 Usamos tus columnas personalizadas para timestamps
    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_update';

    // Si la tabla no tiene timestamps de Laravel (created_at, updated_at)
    public $timestamps = true;

    // 🔹 Accessor para mostrar un nombre de usuario legible
    public function getDisplayNameAttribute(): string
    {
        return $this->username
            ?? ($this->name ?? null)
            ?? ($this->full_name ?? null)
            ?? (trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')) ?: null)
            ?? ($this->email ?? 'User');
    }
}
