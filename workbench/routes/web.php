<?php

use Illuminate\Support\Facades\Route;

Route::post('/single-step', [\Workbench\App\Http\Controllers\SingleStep::class, 'sayAndHangup'])->name('single-step');
