<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DownloadList extends Model
{
    protected $table = 'tbl_download_list';
    protected $guarded = [];

    // public function user()
    // {
    //     return $this->belongsTo(User::class, 'user_id');
    // }

    // public function track()
    // {
    //     return $this->belongsTo(Track::class, 'track_id');
    // }
    // public function release()
    // {
    //     return $this->belongsTo(Release::class, 'release_id');
    // }
}


