<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ListController;
use App\Http\Controllers\Api\V1\PlaceController;
use App\Http\Controllers\Api\V1\RatingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Auth — tighter rate limit
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Email verification (Laravel built-in)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/email/verify/{id}/{hash}', function () {
            // handled by Laravel's built-in email verification
        })->middleware('signed')->name('verification.verify');

        Route::post('/email/resend', function (Request $request) {
            $request->user()->sendEmailVerificationNotification();

            return response()->json(['message' => 'Verification email resent.']);
        })->middleware('throttle:6,1');
    });

    // Standard API rate limit
    Route::middleware('throttle:api')->group(function () {

        // Public
        Route::get('/lists/{list}', [ListController::class, 'show']);
        Route::get('/places/{place}/ratings', [RatingController::class, 'index']);

        // Auth required
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/user', [AuthController::class, 'user']);

            // Lists
            Route::get('/lists', [ListController::class, 'index']);
            Route::post('/lists', [ListController::class, 'store']);
            Route::patch('/lists/{list}', [ListController::class, 'update']);
            Route::delete('/lists/{list}', [ListController::class, 'destroy']);

            // Places within a list
            Route::post('/lists/{list}/places', [PlaceController::class, 'store']);
            Route::delete('/lists/{list}/places/{place}', [PlaceController::class, 'destroy']);

            // Ratings
            Route::post('/places/{place}/ratings', [RatingController::class, 'store']);
            Route::delete('/places/{place}/ratings', [RatingController::class, 'destroy']);
        });
    });
});
