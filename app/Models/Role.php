<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
    ];
    //hidden
    // protected $hidden = [
    //     'created_at',
    //     'updated_at',
    // ];
    public function permissions() {

        return $this->belongsToMany(Permission::class,'roles_permissions');
            
     }
     
    public function users() {
     
        return $this->belongsToMany(User::class,'users_roles');
            
     }

    public function forms() {
        
        return $this->belongsToMany(Form::class,'form_roles' , 'role_id' , 'form_id');

    }   

    
}
