<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Role;
use App\Models\Game;
use App\Models\FavoriteGame;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use JWTAuth;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class FavoriteGameController extends Controller
{
    public function listFavorites()
    {
        try{
            // Obtener usuario del token
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado en el token',
                    'timestamp' => now(),
                ], 401);
            }

            // Verificar rol visitor
            $roles = $user->roles()->pluck('name')->toArray();
            if (!in_array('visitor', $roles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver la lista de categorías',
                    'timestamp' => now(),
                ], 403);
            }

            // 🔥 Cargar SOLO favoritos activos
            $favorites = FavoriteGame::where('user_id', $user->id)
                ->where('status', 'active')   // 👈 AQUÍ EL CAMBIO IMPORTANTE
                ->with([
                    'game.developer',
                    'game.category',
                    'game.platforms'
                ])
                ->get();

            // Agregar image_url
            foreach ($favorites as $fav) {
                if ($fav->game) {
                    $fav->game->image_url = $fav->game->image
                        ? asset('storage/' . $fav->game->image)
                        : null;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Juegos favoritos obtenidos correctamente',
                'data'    => $favorites,
                'timestamp' => now(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error mostrar la lista ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }


    public function findFavoriteById(Request $request, $id)
    {
        try {
            // Obtener usuario desde el token
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado en el token',
                    'timestamp' => now()
                ], 401);
            }

            // Verificar rol "visitor"
            $roles = $user->roles()->pluck('name')->toArray();
            if (!in_array('visitor', $roles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para buscar favoritos',
                    'timestamp' => now()
                ], 403);
            }

            // Buscar el juego favorito del usuario
            $favorite = FavoriteGame::where('user_id', $user->id)
                ->where('game_id', $id)
                ->with([
                    'game.developer',
                    'game.category',
                    'game.platforms'
                ])
                ->first();

            if (!$favorite) {
                return response()->json([
                    'success' => false,
                    'message' => 'El juego no está en favoritos',
                    'timestamp' => now()
                ], 404);
            }

            // Añadir URL de imagen
            if ($favorite->game) {
                $favorite->game->image_url = $favorite->game->image
                    ? asset('storage/' . $favorite->game->image)
                    : null;
            }

            return response()->json([
                'success' => true,
                'message' => 'Juego favorito encontrado correctamente',
                'data' => $favorite,
                'timestamp' => now()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar el favorito: ' . $e->getMessage(),
                'timestamp' => now()
            ], 500);
        }
    }

    //funcion para validar el estatus de favoritos
    //No existe registro //	Se crea y activa
    //Existe y está active // Cambia a inactive
    //Existe y está inactive // Cambia a active
    public function toggleFavorite(Request $request, $id)
    {
        try {
            // Obtener usuario del token
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado en el token',
                    'timestamp' => now(),
                ], 401);
            }

            // Verificar rol
            $roles = $user->roles()->pluck('name')->toArray();
            if (!in_array('visitor', $roles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para gestionar favoritos',
                    'timestamp' => now(),
                ], 403);
            }

            $userId = $user->id;
            $gameId = $id;

            // Verificar que el juego exista
            if (!Game::where('id', $gameId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El juego no existe',
                    'timestamp' => now(),
                ], 404);
            }

            // Buscar favorito existente
            $favorite = FavoriteGame::where('user_id', $userId)
                                    ->where('game_id', $gameId)
                                    ->first();

            // CASO 1: No existe → CREAR y activar
            if (!$favorite) {
                $favorite = FavoriteGame::create([
                    'user_id' => $userId,
                    'game_id' => $gameId,
                    'status'  => 'active'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Juego agregado a favoritos (status: active)',
                    'timestamp' => now(),
                    'data' => $favorite
                ], 200);
            }

            // CASO 2: Existe → Alternar estado
            if ($favorite->status === 'active') {
                $favorite->status = 'inactive';
                $favorite->save();

                return response()->json([
                    'success' => true,
                    'message' => 'El juego fue desactivado de favoritos (status: inactive)',
                    'timestamp' => now(),
                    'data' => $favorite
                ], 200);
            }

            // CASO 3: Existe y está inactive → Activarlo
            if ($favorite->status === 'inactive') {
                $favorite->status = 'active';
                $favorite->save();

                return response()->json([
                    'success' => true,
                    'message' => 'El juego fue reactivado en favoritos (status: active)',
                    'timestamp' => now(),
                    'data' => $favorite
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al gestionar el favorito: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }





}
