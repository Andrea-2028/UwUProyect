<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\JWTMiddleware;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\GameController;


Route::prefix('auth')->group(function () {
    //registros
    Route::post('registerAdmin', [UserController::class, 'registerAdmin']);   // Crear un admin
    Route::post('registerVisit', [UserController::class, 'registerVisit']);   // Crear un visitante

    Route::post('login', [AuthController::class, 'login']);      // Login con 2FA
    Route::post('verify-2fa', [AuthController::class, 'verify2fa']); // Verificar código
    Route::post('users/refresh-token', [AuthController::class, 'refreshToken']);

    //Funciones para restablecer contraseña
    Route::post('UsOp/passResetRequest', [AuthController::class, 'passResetRequest']);
    Route::post('UsOp/validatorCode', [AuthController::class, 'validatorCode']);
    Route::post('UsOp/changePassword', [AuthController::class, 'changePassword']);

    //funciones de juegos
    Route::get('games', [GameController::class, 'index']); // listar todos
    Route::get('games/{id}', [GameController::class, 'show']); // detalle de uno
});

Route::middleware([JWTMiddleware::class])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('my-profile', [UserController::class, 'myProfile']);

    Route::prefix('users')->group(function () {
        Route::put('update-profile/{id}', [UserController::class, 'updateProfile']);   //Editar su informacion de usuario
        Route::put('sofdelete-Account/{id}', [UserController::class, 'deactivateAccount']);
    });

    Route::prefix('categories')->group(function () {
        Route::post('registerCategories', [CategoryController::class, 'store']); // Crear categoría
        Route::get('categoriesList', [CategoryController::class, 'index']); // Listar categorías
        Route::get('categorieShow/{id}', [CategoryController::class, 'show']); // Ver categoría específica
        Route::put('categorieUpdate/{id}', [CategoryController::class, 'update']); // Editar categoría 
        Route::delete('categorieRemove/{id}', [CategoryController::class, 'destroy']); // Eliminar categoría
    });

    Route::prefix('developers')->group(function () {
        Route::post('registerDevelopers', [DeveloperController::class, 'store']); // Crear Desarrollador
        Route::get('developerList', [DeveloperController::class, 'index']); // Listar Desarrolladores
        Route::get('developerShow/{id}', [DeveloperController::class, 'show']); // Ver Desarrollador específico
        Route::put('developerUpdate/{id}', [DeveloperController::class, 'update']); // Editar Desarrollador 
        Route::delete('developerRemove/{id}', [DeveloperController::class, 'destroy']); // Eliminar Desarrollador
    });

    Route::prefix('platforms')->group(function () {
        Route::post('registerPlatform', [PlatformController::class, 'store']); // Crear Desarrollador
        Route::get('platformList', [PlatformController::class, 'index']); // Listar Desarrolladores
        Route::get('platformShow/{id}', [PlatformController::class, 'show']); // Ver Desarrollador específico
        Route::put('platformUpdate/{id}', [PlatformController::class, 'update']); // Editar Desarrollador 
        Route::delete('platformRemove/{id}', [PlatformController::class, 'destroy']); // Eliminar Desarrollador
    });

    Route::prefix('games1')->group(function () {
        Route::post('registerGames', [GameController::class, 'store']); // Agregar juego
        Route::post('games/{id}', [GameController::class, 'update']); //Editar juego
        Route::put('games/deactivate/{id}', [GameController::class, 'deactivate']); //desactivar juego
    });
});