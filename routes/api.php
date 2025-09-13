<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ContentsIndexController;

Route::get('/contents', ContentsIndexController::class);
