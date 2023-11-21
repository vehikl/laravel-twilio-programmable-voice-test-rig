<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\AnonymousDialing;
use Workbench\App\Http\Controllers\InvalidFlows;
use Workbench\App\Http\Controllers\MultipleStep;
use Workbench\App\Http\Controllers\SingleStep;

Route::post('/single-step', [SingleStep::class, 'sayAndHangup'])->name('single-step');

Route::post('/multiple-step/record', [MultipleStep::class, 'record'])->name('multiple-step.record');
Route::post('/multiple-step/thanks', [MultipleStep::class, 'thanks'])->name('multiple-step.thanks');
Route::post('/multiple-step/empty-recording-retry', [MultipleStep::class, 'emptyRecordingRetry'])->name('multiple-step.emptyRecordingRetry');
Route::post('/multiple-step/status-change', [MultipleStep::class, 'statusChange'])->name('multiple-step.statusChange');

Route::post('/anonymous-dialing/gather', [AnonymousDialing::class, 'gather'])->name('anonymous-dialing.gather');
Route::post('/anonymous-dialing/dial', [AnonymousDialing::class, 'dial'])->name('anonymous-dialing.dial');
Route::post('/anonymous-dialing/completed', [AnonymousDialing::class, 'completed'])->name('anonymous-dialing.completed');
Route::post('/anonymous-dialing/failed', [AnonymousDialing::class, 'failed'])->name('anonymous-dialing.failed');

Route::post('/invalid-flows/json', [InvalidFlows::class, 'json'])->name('invalid-flows.json');
Route::post('/invalid-flows/string', [InvalidFlows::class, 'string'])->name('invalid-flows.string');
Route::post('/invalid-flows/redirect-not-found', [InvalidFlows::class, 'redirectNotFound'])->name('invalid-flows.redirectNotFound');
Route::post('/invalid-flows/redirect-to-server-error', [InvalidFlows::class, 'redirectToServerError'])->name('invalid-flows.redirectToServerError');
Route::post('/invalid-flows/server-error', [InvalidFlows::class, 'serverError'])->name('invalid-flows.serverError');
