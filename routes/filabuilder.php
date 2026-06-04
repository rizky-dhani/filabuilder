<?php

use Filabuilder\Http\Controllers\BlockController;
use Filabuilder\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::prefix('filabuilder/api')
    ->middleware('web')
    ->name('filabuilder.')
    ->group(function () {
        Route::get('blocks', [BlockController::class, 'index'])->name('blocks');
        Route::post('page/{page}/save', [PageController::class, 'save'])->name('page.save');
    });

Route::get('builder/{page}', [PageController::class, 'builder'])
    ->middleware('web')
    ->name('filabuilder.builder');

Route::post('builder/{page}', [PageController::class, 'builderSave'])
    ->middleware('web')
    ->name('filabuilder.builder.save');
