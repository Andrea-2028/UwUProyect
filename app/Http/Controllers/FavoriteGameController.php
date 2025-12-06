<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FavoriteGameController extends Controller
{
    public function listFavorites()
    {
        $userId = auth()->id();

        $favorites = FavoriteGame::where('user_id', $userId)
                    ->with('game')
                    ->get();

        return response()->json($favorites);
    }

    public function addFavorite(Request $request)
    {
        $userId = auth()->id();
        $gameId = $request->game_id;

        // Validar que el juego existe
        $gameExists = Game::where('id', $gameId)->exists();
        if (!$gameExists) {
            return response()->json(['message' => 'El juego no existe'], 404);
        }

        // Verificar si ya está en favoritos
        $favorite = FavoriteGame::where('user_id', $userId)
                                ->where('game_id', $gameId)
                                ->first();

        if ($favorite) {
            return response()->json(['message' => 'El juego ya está en favoritos'], 409);
        }

        FavoriteGame::create([
            'user_id' => $userId,
            'game_id' => $gameId
        ]);

        return response()->json(['message' => 'Juego agregado a favoritos']);
    }

    public function removeFavorite(Request $request)
    {
        $userId = auth()->id();
        $gameId = $request->game_id;

        $favorite = FavoriteGame::where('user_id', $userId)
                                ->where('game_id', $gameId)
                                ->first();

        if (!$favorite) {
            return response()->json(['message' => 'Este juego no está en favoritos'], 404);
        }

        $favorite->delete();

        return response()->json(['message' => 'Juego eliminado de favoritos']);
    }


}
