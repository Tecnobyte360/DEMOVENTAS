<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guard_name = 'web'; // O 'api' según corresponda
    protected  $fillable = ['name','guard_name', 'description'];
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions', 'role_id', 'permission_id'); // Utiliza el nombre de la tabla y las columnas personalizadas
    }
    
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles'); // Utiliza el nombre de la tabla personalizado
    }
    
    
    
}
