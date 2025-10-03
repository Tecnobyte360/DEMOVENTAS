<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{

    protected $guarded = ['id'];
    protected $fillable = ["name", "guard_name", "description"];
    public function users()
    {
        return $this->belongsToMany(config('auth.providers.users.model'));
    }

    /**
     * Roles que tienen este permiso.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
