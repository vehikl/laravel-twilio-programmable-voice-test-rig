<?php

use Illuminate\Support\Facades\Route;

Route::get('/First', [\Workbench\App\Http\Controllers\First::class, 'stepOne'])->name('stepOne');
Route::get('/Second', [\Workbench\App\Http\Controllers\First::class, 'stepTwo'])->name('stepTwo');
