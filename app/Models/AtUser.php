<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; 
use Illuminate\Notifications\Notifiable;

class AtUser extends Authenticatable
{
    use HasFactory, Notifiable;

    
    protected $table = 'at_users';

    
    protected $primaryKey = 'uid';

    
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

    
    const CREATED_AT = 'date_creation';
    const UPDATED_AT = 'date_update';

    
    public $timestamps = true;

    
    public function getDisplayNameAttribute(): string
    {
        return $this->username
            ?? ($this->name ?? null)
            ?? ($this->full_name ?? null)
            ?? (trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')) ?: null)
            ?? ($this->email ?? 'User');
    }
}
