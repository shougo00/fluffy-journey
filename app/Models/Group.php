<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'name',
        'host_user_id',
        'invite_code'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_user');
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }
}