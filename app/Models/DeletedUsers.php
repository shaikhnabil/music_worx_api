<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedUsers extends Model
{
    protected $table = 'tbl_deleted_users';
    protected $guarded = [];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
