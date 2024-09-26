<?php

use App\Http\Controllers\Map;
use App\Http\Controllers\Metadata;
use App\Http\Controllers\Tiles;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', Map::class);

Route::get('/api/tiles/{zoom}/{x}/{z}', [Tiles::class, 'render'])
    ->whereNumber('zoom')
    // Unfortunately, not just `whereNumber` because we need to support negative coordinates!
    ->where(['x', 'z'], '-?[0-9]+')
    ->name('tile');

Route::get('/api/level', [Metadata::class, 'level']);
Route::get('/api/players', [Metadata::class, 'players']);
Route::get('/api/poi/{x1},{z1}/{x2},{z2}', [Metadata::class, 'poi'])
    // Same as above, `whereNumber` does not support negative values!
    ->where(['x1', 'z1', 'x2', 'z2'], '-?[0-9]+');
