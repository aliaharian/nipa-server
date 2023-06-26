<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'parent_id'
    ];
    public function roles()
    {

        return $this->belongsToMany(Role::class, 'roles_permissions');

    }

    //parent
    public function parent()
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }

    //childs
    public function childs()
    {
        return $this->hasMany(Permission::class, 'parent_id');
    }
    public function users()
    {

        return $this->belongsToMany(User::class, 'users_permissions');

    }
}