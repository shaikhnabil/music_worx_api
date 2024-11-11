<?php

use App\Http\Controllers\Api\LikedTracks;
use App\Http\Controllers\Api\Login;
use App\Http\Controllers\Api\Playlist;
use App\Http\Controllers\Api\PurchasedTracks;
use App\Http\Controllers\Api\RefreshAccessToken;
use App\Http\Controllers\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\GetAccessToken;

Route::post('login',[Login::class, 'login']);
Route::post('/get_access_token', [GetAccessToken::class, 'getToken']);
Route::post('/refresh_access_token', [RefreshAccessToken::class, 'refreshAccessToken']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/liked_tracks', [LikedTracks::class, 'likedTracks']);
    Route::post('/purchased_tracks', [PurchasedTracks::class, 'index']);
    Route::post('playlist/all', [Playlist::class, 'index']);
    Route::post('playlist/single', [Playlist::class, 'show']);
    Route::post('playlist/create', [Playlist::class, 'create']);
    Route::post('playlist/update', [Playlist::class, 'update']);
    Route::post('playlist/delete', [Playlist::class, 'delete']);
    Route::post('playlist/add_items', [Playlist::class, 'addTracks']);
    Route::post('playlist/remove_items', [Playlist::class, 'removeTracks']);
    Route::post('search/tracks', [SearchController::class, 'search_tracks']);
    Route::post('search/releases', [SearchController::class, 'search_release']);
    Route::post('search/artists', [SearchController::class, 'search_artist']);
    Route::post('search/labels', [SearchController::class, 'search_label']);
    Route::post('/logout', [Login::class, 'logout']);
});

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
