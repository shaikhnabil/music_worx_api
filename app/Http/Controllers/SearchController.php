<?php

namespace App\Http\Controllers;

use App\Http\Responses\TheOneResponse;
use App\Models\Elastic;
use App\Models\Mp3Mix;
use App\Models\Release;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{

    protected $elastic;

    public function __construct(Elastic $elastic)
    {
        $this->elastic = $elastic;
    }
    //search tracks
    public function search_tracks(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string',
            'limit' => 'nullable|integer|min:1',
            'offset' => 'nullable|integer|min:0',
        ]);

        $keyword = $request->input('keyword');
        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 0);

        // $query = Mp3Mix::query()
        //     ->where('song_name', 'like', "%{$keyword}%")
        //    // ->orWhere('artist_name', 'like', "%{$keyword}%")
        //     ->orWhere('mix_name', 'like', "%{$keyword}%")
        //     //->orderBy('release.original_release_date', 'desc')
        //     ->offset($offset)->limit($limit)->get();

        $query = Mp3Mix::where('song_name', 'like', "%{$keyword}%")
            ->orWhere('mix_name', 'like', "%{$keyword}%")
            ->orWhereHas('artists', function ($q) use ($keyword) {
                $q->where('artist_name', 'like', "%{$keyword}%");
            })
            ->with([
                'artists' => function ($query) {
                    $query->select('tbl_users.id', 'artist_name');
                },
                'release' => function ($query) {
                    $query->select('tbl_release.id', 'release_id', 'original_release_date', 'label');
                }
            ])->offset($offset)->limit($limit)->get();


        if ($query->isEmpty()) {
            return TheOneResponse::notFound('Track not found');
        } else {
            return TheOneResponse::ok(['searched_tracks' => $query], 'Tracks found');
        }
    }

    //search release
    public function search_release(Request $request)
    {
        $term = $request->input('keyword');
        $numRecPerPage = $request->input('per_page', 10);
        $startFrom = $request->input('page', 0);

        $results = $this->elastic->search_releases($term, $numRecPerPage, $startFrom);
        return TheOneResponse::ok(['searched_release' => $results], 'Release found');
        //return response()->json($results);
    }
    // public function search_release(Request $request)
    // {
    //     $request->validate([
    //         'keyword' => 'required|string',
    //         'limit' => 'nullable|integer|min:1',
    //         'offset' => 'nullable|integer|min:0',
    //     ]);

    //     $keyword = $request->input('keyword');
    //     $limit = $request->input('limit', 10);
    //     $offset = $request->input('offset', 0);

    //     $query = Release::where('title', 'like', "%{$keyword}%")
    //         ->orWhere('description', 'like', "%{$keyword}%")
    //         ->orWhereHas('tracks.artists', function ($q) use ($keyword) {
    //             $q->where('artist_name', 'like', "%{$keyword}%");
    //         })
    //         // 'id','user_id', 'release_id', 'track_uplod_id', 'song_name','mix_name', 'lyrics', 'slug', 'promotion', 'composer', 'publisher','producer', 'mixing_engineer', 'remixer', 'isrc_code', 'gener', 'mp3_file', 'mp3_sd', 'mp3_preview', 'flac_file', 'duration','full_wf', 'full_gwf', 'waveform', 'green_waveform',
    //         ->with([
    //             'tracks' => function ($query) {
    //                 $query->select('tbl_mp3_mix.*')
    //                     ->with([
    //                         'artists' => function ($query) {
    //                             $query->select('tbl_users.id', 'artist_name');
    //                         },
    //                         'release' => function ($query) {
    //                             $query->select('release_id');
    //                         }
    //                     ]);
    //             }
    //         ])
    //         ->offset($offset)
    //         ->limit($limit)
    //         ->get();


    //     if ($query->isEmpty()) {
    //         return TheOneResponse::notFound('Release not found');
    //     } else {
    //         return TheOneResponse::ok(['searched_release' => $query], 'Release found');
    //     }
    // }

    //search artist
    public function search_artist(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string',
            'limit' => 'nullable|integer|min:1',
            'offset' => 'nullable|integer|min:0',
        ]);

        $keyword = $request->input('keyword');
        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 0);

        $searchTerms = explode(' ', $keyword);

        // $query = User::query()
        //     ->where('usertype', 'artist')
        //     ->where(function ($q) use ($keyword, $searchTerms) {
        //         $q->where('artist_name', 'like', '%' . $keyword . '%')
        //             ->orWhere('artist_name', '=', $keyword);

        //         foreach ($searchTerms as $searchWord) {
        //             $q->orWhere('artist_name', 'like', '%' . $searchWord . '%');
        //         }
        //     })
        //     ->leftJoin('tbl_track_artists as ta', 'ta.artist_id', '=', 'tbl_users.id')
        //     ->select(
        //         'tbl_users.id as artist',
        //         \DB::raw('MAX(tbl_users.artist_name) as artist_name'),
        //         \DB::raw('MAX(tbl_users.usertype) as usertype'),
        //         \DB::raw('MAX(tbl_users.mw_user) as mw_user'),
        //         \DB::raw('count(ta.id) as total_tracks')
        //     )
        //     ->groupBy('tbl_users.id') // Group only by the primary key
        //     ->havingRaw('total_tracks > 0');


        $query = User::query()
            ->where('usertype', 'artist')
            ->where(function ($q) use ($keyword, $searchTerms) {
                $q->where('artist_name', 'like', '%' . $keyword . '%')
                    ->orWhere('artist_name', '=', $keyword);

                foreach ($searchTerms as $searchWord) {
                    $q->orWhere('artist_name', 'like', '%' . $searchWord . '%');
                }
            })
            ->leftJoin('tbl_track_artists as ta', 'ta.artist_id', '=', 'tbl_users.id')
            // ->select('tbl_users.*', 'tbl_users.id as artist', \DB::raw('count(ta.id) as total_tracks'))
            ->select('tbl_users.*', 'tbl_users.id as artist');
        // ->groupBy('tbl_users.id')->havingRaw('total_tracks > 0');

        $artist = $query->offset($offset)->limit($limit)->get();

        if ($artist->isEmpty()) {
            return TheOneResponse::notFound('Artist not found');
        } else {
            return TheOneResponse::ok(['searched_artist' => $artist], 'Artist found');
        }
    }

    // search label
    public function search_label(Request $request)
    {

    }



}
