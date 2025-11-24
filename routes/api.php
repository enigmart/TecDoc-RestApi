<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\VehicleSearchController;
use App\Http\Controllers\Api\OptimizedVehicleSearchController;
use App\Http\Controllers\Api\ArticleDetailsController;
use App\Http\Controllers\Api\V2\AuthController;
use App\Http\Controllers\Api\V2\VehicleController as V2VehicleController;
use App\Http\Controllers\Api\V2\ArticleController as V2ArticleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('api')->group(function () {
    
    // Articles API
    Route::prefix('articles')->group(function () {
        Route::get('/', [ArticleController::class, 'index']);
        Route::get('/{id}', [ArticleController::class, 'show']);
        Route::post('/search', [ArticleController::class, 'searchByNumber']);
        Route::get('/supplier/{supplierId}', [ArticleController::class, 'bySupplier']);
    });
    
    // Suppliers API
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);
        Route::get('/{id}', [SupplierController::class, 'show']);
        Route::get('/{id}/articles', [SupplierController::class, 'withArticles']);
        Route::post('/search', [SupplierController::class, 'searchByBrand']);
    });
    
    // Vehicle Search API (Advanced Search like todos.gr)
    Route::prefix('vehicle-search')->group(function () {
        Route::get('/vehicle-types', [VehicleSearchController::class, 'getVehicleTypes']);
        Route::get('/manufacturers', [VehicleSearchController::class, 'getManufacturers']);
        Route::get('/manufacturers/{id}/models', [VehicleSearchController::class, 'getModelSeries']);
        Route::get('/models/{id}/versions', [VehicleSearchController::class, 'getVehicleVersions']);
        Route::get('/part-categories', [VehicleSearchController::class, 'getPartCategories']);
        Route::post('/search-articles', [VehicleSearchController::class, 'searchArticles']);
        Route::get('/articles/{id}/details', [VehicleSearchController::class, 'getArticleDetails']);
    });
    
    // Optimized Vehicle Search API with Redis Caching
    Route::prefix('optimized-search')->group(function () {
        Route::get('/vehicle-types', [OptimizedVehicleSearchController::class, 'getVehicleTypes']);
        Route::get('/manufacturers', [OptimizedVehicleSearchController::class, 'getManufacturers']);
        Route::get('/manufacturers/{id}/models', [OptimizedVehicleSearchController::class, 'getModelSeries']);
        Route::get('/models/{id}/versions', [OptimizedVehicleSearchController::class, 'getVehicleVersions']);
        Route::get('/part-categories', [OptimizedVehicleSearchController::class, 'getPartCategories']);
        Route::post('/search-articles', [OptimizedVehicleSearchController::class, 'searchArticles']);
        
        // Cache management endpoints
        Route::get('/cache/stats', [OptimizedVehicleSearchController::class, 'getCacheStats']);
        Route::delete('/cache/clear', [OptimizedVehicleSearchController::class, 'clearCaches']);
    });

    // Article Details API
    Route::prefix('articles')->group(function () {
        Route::get('/{id}/details', [ArticleDetailsController::class, 'getDetails']);
    });
    
    // API v2 Authentication Routes (Public)
    Route::prefix('v2/auth')->group(function () {
        Route::post('/create-token', [AuthController::class, 'createToken']);
    });

    // API v2 Authenticated Routes
    Route::prefix('v2')->middleware(['api.auth', 'api.cache'])->group(function () {
        // Token management
        Route::get('/auth/token-info', [AuthController::class, 'tokenInfo']);
        Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
        Route::delete('/auth/revoke-token', [AuthController::class, 'revokeToken']);
        
        // Vehicle endpoints (with light/full modes)
        Route::get('/vehicle-types', [V2VehicleController::class, 'getVehicleTypes']);
        Route::get('/manufacturers', [V2VehicleController::class, 'getManufacturers']);
        Route::get('/manufacturers/{id}/models', [V2VehicleController::class, 'getModelSeries']);
        Route::get('/models/{id}/versions', [V2VehicleController::class, 'getVehicleVersions']);
        
        // Article endpoints (with light/full modes)
        Route::get('/part-categories', [V2ArticleController::class, 'getPartCategories']);
        Route::post('/search-articles', [V2ArticleController::class, 'searchArticles']);
        Route::get('/articles/{id}/details', [V2ArticleController::class, 'getArticleDetails']);
    });
    
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'OK',
            'timestamp' => now(),
            'database' => 'connected'
        ]);
    });
    
});
