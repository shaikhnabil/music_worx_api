<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mp3Mix extends Model
{
    protected $table = 'tbl_mp3_mix';
    protected $guarded = [];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function release()
    {
        return $this->belongsTo(Release::class, 'release_id','release_id');
    }

    public function artists()
    {
        return $this->belongsToMany(User::class, 'tbl_track_artists', 'track_id', 'artist_id');
    }

    protected $appends = ['cover250x250', 'cover100x100'];

    public function getCover250x250Attribute()
    {
        if ($this->release && $this->release->cover) {
            $fname = explode('.', $this->release->cover);
            return "https://images.music-worx.com/covers/" . $fname[0] . '-250x250.jpg';
        }
        return "https://images.music-worx.com/covers/default-250x250.jpg";
    }

    public function getCover100x100Attribute()
    {
        if ($this->release && $this->release->cover) {
            $fname = explode('.', $this->release->cover);
            return "https://images.music-worx.com/covers/" . $fname[0] . '-100x100.jpg';
        }
        return "https://images.music-worx.com/covers/default-100x100.jpg";
    }

    // public function artists()
    // {
    //     return $this->hasMany(TrackArtists::class, 'artist_id', 'id');
    // }

}
