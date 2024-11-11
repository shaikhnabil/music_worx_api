<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\TheOneResponse;
use App\Models\OrderedBasket;
use App\Models\UsersMobileToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Libraries\Cloud_lib;

class PurchasedTracks extends Controller
{
    public function indexes(Request $request)
    {
        $user_token = $request->input('user_token');
        $limit = $request->input('limit', 30);
        $offset = $request->input('offset', 0);

        if (!$user_token) {
            //return $this->sendError('User token is required.', 400);
            return TheOneResponse::InvalidCredential('User token is required.');
        }

        $userMobileToken = UsersMobileToken::where('token', $user_token)->first();

        if (!$userMobileToken) {
            // return $this->sendError('Invalid user token', 401);
            return TheOneResponse::InvalidCredential('Invalid user token.');
        }

        $user_id = $userMobileToken->user_id;

        //$purchasedTracks = [];

        // $query = OrderedBasket::where('user_id', $user_id)->with('items');

        $query = OrderedBasket::where('user_id', $user_id)->with([
            'items' => function ($query) {
                $query->with(['track', 'release.tracks']);
            }
        ])->limit($limit);

        // if ($limit > 0) {
        //     $query->limit($limit)->offset($offset);
        // }

        $orderedBaskets = $query->get();
        $purchasedTracks = collect();
        $cloud_lib = new Cloud_lib();

        foreach ($orderedBaskets as $basket) {
            foreach ($basket->items as $item) {
                if ($item->release_or_track === 'track' && $item->track) {
                    $purchasedTracks->push($item->track);
                } elseif ($item->release_or_track === 'release' && $item->release) {
                    $purchasedTracks = $purchasedTracks->merge($item->release->tracks);
                }
            }
            // Get Preview URL
            if ($basket->mp3_preview != $basket->mp3_file) {
                $previewUrl = $cloud_lib->get_signed_url($basket->mp3_preview, 'music_bucket', 'prev');
            } else {
                if (!is_null($basket->mp3_sd)) {
                    $previewUrl = $cloud_lib->get_signed_url($basket->mp3_sd, 'music_bucket', 'new_release');
                } else {
                    $previewUrl = $cloud_lib->get_signed_url($basket->mp3_file, 'music_bucket', 'new_release');
                }
            }
            $purchasedTracks['mp3_preview'] = $previewUrl;
        }

        $paginatedTracks = $purchasedTracks->slice($offset, $limit)->values();

        // foreach ($orderedBaskets as $basket) {
        //     foreach ($basket->items as $item) {
        //         if ($item->release_or_track === 'track') {
        //             $track = $item->track()->first();
        //             if ($track) {
        //                 $purchasedTracks[] = $track;
        //             }
        //         } elseif ($item->release_or_track === 'release') {
        //             $release = $item->release()->with('tracks')->first();
        //             if ($release) {
        //                 foreach ($release->tracks as $track) {
        //                     $purchasedTracks[] = $track;
        //                 }
        //             }
        //         }
        //     }
        // }

        //return $this->sendResponse($paginatedTracks, 'Purchased Tracks retrived successfully.', 200);
        return TheOneResponse::ok(['purchased_tracks' => $paginatedTracks], 'Purchased Tracks retrived successfully.');
    }

    public function index(Request $request)
    {
        $user_token = $request->input('user_token');
        $limit = (int) $request->input('limit', 30);
        $offset = (int) $request->input('offset', 0);

        if (!$user_token) {
            return TheOneResponse::InvalidCredential('User token is required.');
        }

        $userMobileToken = UsersMobileToken::where('token', $user_token)->first();

        if (!$userMobileToken) {
            return TheOneResponse::InvalidCredential('Invalid user token.');
        }

        $user_id = $userMobileToken->user_id;

        $orderedBaskets = OrderedBasket::where('user_id', $user_id)
            ->with([
                'items.track',
                'items.release.tracks'
            ])->offset($offset)
            ->limit($limit)
            ->get();

        $purchasedTracks = collect();
        //$cloud_lib = new Cloud_lib();

        foreach ($orderedBaskets as $basket) {
            foreach ($basket->items as $item) {
                $trackData = null;

                if ($item->release_or_track === 'track' && $item->track) {
                    $trackData = collect([$item->track]);
                } elseif ($item->release_or_track === 'release' && $item->release) {
                    $trackData = $item->release->tracks;
                }

                if ($trackData) {
                    foreach ($trackData as $track) {
                        // if ($basket->mp3_preview !== $basket->mp3_file) {
                        //     $track['preview_url'] = $cloud_lib->get_signed_url($basket->mp3_preview, 'music_bucket', 'prev');
                        // } elseif (!is_null($basket->mp3_sd)) {
                        //     $track['preview_url'] = $cloud_lib->get_signed_url($basket->mp3_sd, 'music_bucket', 'new_release');
                        // } else {
                        //     $track['preview_url'] = $cloud_lib->get_signed_url($basket->mp3_file, 'music_bucket', 'new_release');
                        // }
                        $purchasedTracks->push($track);
                    }
                }
            }
        }

        if ($purchasedTracks->isEmpty()) {
            return TheOneResponse::notFound('Purchased tracks not found.');
        }

        // Paginate tracks
        $paginatedTracks = $purchasedTracks->slice(0, $limit)->values();

        return TheOneResponse::ok(['purchased_tracks' => $paginatedTracks], 'Purchased Tracks retrieved successfully.');
    }
}
