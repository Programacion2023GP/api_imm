<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'usuario',
        'password',
        'nombre_completo',
        'id_rol',
        'activo',
    ];

    protected $hidden = [
        'password',
    ];

    // Configurar Sanctum para usar 'usuario' en lugar de 'email'
    public function findForPassport($username)
    {
        return $this->where('usuario', $username)->first();
    }

    // Método necesario para Sanctum (aunque no uses email)
    public function getEmailForVerification()
    {
        return $this->usuario; // O retorna null si no aplica
    }

    // Si necesitas el campo 'email' para algo, crea un accessor
    public function getEmailAttribute()
    {
        return $this->usuario; // Usa el campo 'usuario' como si fuera email
    }
}
