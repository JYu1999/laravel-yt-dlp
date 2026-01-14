<?php

declare(strict_types=1);

use App\Http\Controllers\Api\DownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/downloads/{id}', [DownloadController::class, 'show']);
