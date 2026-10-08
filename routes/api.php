<?php

use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\VideoApiController;
use Illuminate\Support\Facades\Route;

// Public JSON APIs — all protected by a static bearer token (Authorization: Bearer <token>).
Route::middleware('api.token')->group(function () {
    // API #1 — list categories (each with its last 5 videos)
    Route::get('/get-category', [CategoryApiController::class, 'index']);

    // API #2 — videos by category id
    Route::post('/getVideoByCategoryID', [VideoApiController::class, 'byCategory']);

    // API #3 — mixed feed: all categories' ep1, then all categories' ep2, ... (paginated)
    Route::post('/getrendomvideo', [VideoApiController::class, 'getRandom']);
});


// Example GET API without token requirement
Route::get('/test-api', function (Request $request) {
    try {
        // You can add your actual logic here
        $isSuccess = true; // Use this variable to simulate success or failure

        if ($isSuccess) {
            // Success response with status code 200
            return response()->json([
                'status' => true,
                'message' => 'API executed successfully!'
            ], 200);
        } else {
            // Failure response with status code 400 (Bad Request) or other appropriate code
            return response()->json([
                'status' => false,
                'message' => 'API failed. Specific condition not met.'
            ], 400);
        }

    } catch (\Exception $e) {
        // Exception/server error response with status code 500
        return response()->json([
            'status' => false,
            'message' => 'An unexpected server error occurred.',
            'error' => $e->getMessage()
        ], 500);
    }
});
