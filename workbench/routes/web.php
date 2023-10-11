<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\MultipleStep;
use Workbench\App\Http\Controllers\SingleStep;

Route::post('/single-step', [SingleStep::class, 'sayAndHangup'])->name('single-step');

Route::post('/multiple-step/record', [MultipleStep::class, 'record'])->name('multiple-step.record');
Route::post('/multiple-step/thanks', [MultipleStep::class, 'thanks'])->name('multiple-step.thanks');
Route::post('/multiple-step/empty-recording-retry', [MultipleStep::class, 'emptyRecordingRetry'])->name('multiple-step.emptyRecordingRetry');
