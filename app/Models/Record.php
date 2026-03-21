<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'tate_no',
        'practice_type'
    ];

    public function shots()
    {
        return $this->hasMany(Shot::class)->orderBy('shot_no');
    }
}