<?php

use App\Http\Controllers\Api\V1\MatchController;
use App\Http\Controllers\Api\V1\PredictionController;
use App\Http\Controllers\Api\V1\ProjectionController;
use App\Http\Controllers\Api\V1\RatingController;
use App\Http\Controllers\Api\V1\SeasonController;
use App\Http\Controllers\Api\V1\StandingsController;
use App\Http\Controllers\Api\V1\TeamController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('seasons', [SeasonController::class, 'index'])->name('seasons.index');
    Route::get('seasons/{season}/teams', [TeamController::class, 'index'])->name('seasons.teams.index');
    Route::get('seasons/{season}/matches', [MatchController::class, 'index'])->name('seasons.matches.index');
    Route::get('seasons/{season}/standings', [StandingsController::class, 'show'])->name('seasons.standings.show');
    Route::get('seasons/{season}/ratings', [RatingController::class, 'index'])->name('seasons.ratings.index');
    Route::get('matches/{match}/prediction', [PredictionController::class, 'show'])->name('matches.prediction.show');
    Route::get('seasons/{season}/projections', [ProjectionController::class, 'show'])->name('seasons.projections.show');
});
