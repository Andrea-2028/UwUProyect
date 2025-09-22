<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Game;
use Tymon\JWTAuth\Facades\JWTAuth;


class GameController extends Controller
{
    public function store(Request $request)
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

            // Verificar rol admin
            $roles = $user->roles()->pluck('name')->toArray();
            if (!in_array('admin', $roles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para crear Plataformas',
                    'timestamp' => now(),
                ], 403);
            }

            $validator = \Validator::make($request->all(), [
                'title'        => 'required|string|max:100|unique:games,title',
                'description'  => 'required|string|max:500|',
                'last_update'  => 'required|date',
                'release_date' => 'required|date',
                'image'        => 'required|image|mimes:jpg,jpeg,png|max:2048', // aquí validamos imagen
                'developer_id' => 'required|exists:developers,id',
                'category_id'  => 'required|exists:categories,id',
                'platform_ids'   => 'required|array|min:1',
                'platform_ids.*' => 'exists:platforms,id',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                if ($errors->has('title')) {
                    $msg = 'El Titulo o nombre del juego es obligatorio y debe tener máximo 100 caracteres.';
                } elseif ($errors->has('description')) {
                    $msg = 'La descripcion es obligatoria y debe tener máximo 500 caracteres.';
                } elseif ($errors->has('last_update')) {
                    $msg = 'La ultima_actualización es obligatoria y el formato deve ser de fecha ej: "2025-09-20"';
                } elseif ($errors->has('release_date')) {
                    $msg = 'La fecha_lanzamiento es obligatoria y el formato deve ser de fecha ej: "2025-09-20"';
                } elseif ($errors->has('image')) {
                    $msg = 'La imagen es obligatoria checate el formato, jpg,jpeg,png';
                }  elseif ($errors->has('developer_id')) {
                    $msg = 'El desarrollador es obligatorio y debe de existir en la base de datos';
                }  elseif ($errors->has('category_id')) {
                    $msg = 'La categoria es obligatoria y debe de existir en la base de datos';
                }  elseif ($errors->has('platform_ids')) {
                    $msg = 'Ingresa una plataforma como min y debe de existir en la base de datos';
                } else {
                    $msg = 'Datos inválidos.';
                }
                return response()->json([
                    'success'   => false,
                    'message'   => $msg,
                    'errors'    => $errors,
                    'timestamp' => now(),
                ], 400);
            }

            $data = $validator->validated();

            // Guardar la imagen si viene
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('games', 'public');
                $data['image'] = $path; // guardamos solo la ruta relativa
            }

            $data['status'] = 'active';
            $game = Game::create($data);

            // Asociar plataformas con status = 'active'
            $platformData = collect($data['platform_ids'])->mapWithKeys(function ($id) {
                return [$id => ['status' => 'active']];
            })->toArray();

             $game->platforms()->attach($platformData);

            return response()->json([
                'success' => true,
                'message' => 'Juego creado correctamente',
                'data'    => $game->makeHidden(['created_at', 'updated_at']),
                'image_url' => $game->image ? asset('storage/'.$game->image) : null,
                'timestamp' => now(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear juego: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    public function index()
    {
        try {
            $games = Game::with(['developer', 'category', 'platforms'])
                        ->where('status', 'active')
                        ->orderBy('title', 'asc')
                        ->get();

            if ($games->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay juegos registrados, agrega uno primero.',
                    'timestamp' => now(),
                ], 404);
            }

            // Agregamos la URL pública de la imagen
            $games->transform(function ($game) {
                $game->image_url = $game->image ? asset('storage/' . $game->image) : null;
                return $game;
            });

            return response()->json([
                'success' => true,
                'message' => 'Listado de juegos obtenido correctamente',
                'data'    => $games->makeHidden(['created_at', 'updated_at']),
                'timestamp' => now(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener juegos: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $game = Game::with(['developer', 'category', 'platforms'])
                        ->find($id);

            if (!$game) {
                return response()->json([
                    'success' => false,
                    'message' => 'Juego no encontrado',
                    'timestamp' => now(),
                ], 404);
            }

            // Agregamos la URL pública de la imagen
            $game->image_url = $game->image ? asset('storage/' . $game->image) : null;

            return response()->json([
                'success' => true,
                'message' => 'Juego obtenido correctamente',
                'data'    => $game,
                'timestamp' => now(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el juego: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }
     
    //Aunn esta en fase de prubeas mamahuevo
    public function update(Request $request, $id)
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

            // Verificar rol admin
            $roles = $user->roles()->pluck('name')->toArray();
            if (!in_array('admin', $roles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para crear Plataformas',
                    'timestamp' => now(),
                ], 403);
            }

            // Buscar juego
            $game = Game::find($id);
            if (!$game) {
                return response()->json([
                    'success' => false,
                    'message' => 'Juego no encontrado',
                    'timestamp' => now(),
                ], 404);
            }

            // Validaciones
            $validator = \Validator::make($request->all(), [
                'title' => 'nullable|string|max:100|unique:games,title,' . $game->id,
                'description'  => 'nullable|string|max:500|',
                'last_update'  => 'nullable|date',
                'release_date' => 'nullable|date',
                'image'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // aquí validamos imagen
                'developer_id' => 'nullable|exists:developers,id',
                'category_id'  => 'nullable|exists:categories,id',
                'platform_ids'   => 'nullable|array|min:1',
                'platform_ids.*' => 'exists:platforms,id',
            ]);
            
            if ($validator->fails()) {
                $errors = $validator->errors();
                if ($errors->has('title')) {
                    $msg = 'El Titulo o nombre del juego debe tener máximo 100 caracteres.';
                } elseif ($errors->has('description')) {
                    $msg = 'La descripcion debe tener máximo 500 caracteres.';
                } elseif ($errors->has('last_update')) {
                    $msg = 'La ultima_actualización deve ser de fecha ej: "2025-09-20"';
                } elseif ($errors->has('release_date')) {
                    $msg = 'La fecha_lanzamiento deve ser de fecha ej: "2025-09-20"';
                } elseif ($errors->has('image')) {
                    $msg = 'La imagen checate el formato, jpg,jpeg,png';
                }  elseif ($errors->has('developer_id')) {
                    $msg = 'El desarrollador debe de existir en la base de datos';
                }  elseif ($errors->has('category_id')) {
                    $msg = 'La categoria debe de existir en la base de datos';
                }  elseif ($errors->has('platform_ids')) {
                    $msg = 'Una plataforma como min y debe de existir en la base de datos';
                } else {
                    $msg = 'Datos inválidos.';
                }
                return response()->json([
                    'success'   => false,
                    'message'   => $msg,
                    'errors'    => $errors,
                    'timestamp' => now(),
                ], 400);
            }

            // Obtener solo campos con valor real
            $dataToUpdate = collect($validator->validated())
                ->except(['platform_ids'])
                ->filter(fn($v) => $v !== null && $v !== '')
                ->toArray();

            if (empty($dataToUpdate)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes enviar al menos un campo para actualizar.',
                ], 400);
            }

            // Procesar imagen si viene
            if ($request->hasFile('image')) {
                // Borrar la imagen anterior si existe
                if ($game->image && \Storage::exists('public/' . $game->image)) {
                    \Storage::delete('public/' . $game->image);
                }
                $path = $request->file('image')->store('games', 'public');
                $dataToUpdate['image'] = $path;
            }

            // --- Manejo de plataformas ---
            if (!empty($validated['platform_ids'])) {
                $newPlatformIds = $validated['platform_ids'];

                // Plataformas actuales
                $currentPlatforms = $game->platforms()->pluck('platforms.id')->toArray();

                // Desactivar las que ya no están
                $toDeactivate = array_diff($currentPlatforms, $newPlatformIds);
                foreach ($toDeactivate as $platformId) {
                    $game->platforms()->updateExistingPivot($platformId, ['status' => 'inactive']);
                }

                // Activar o crear las que vienen en la nueva lista
                foreach ($newPlatformIds as $platformId) {
                    $relation = $game->platforms()->where('platform_id', $platformId)->first();
                    if (!$relation) {
                        $game->platforms()->attach($platformId, ['status' => 'active']);
                    } else {
                        $game->platforms()->updateExistingPivot($platformId, ['status' => 'active']);
                    }
                }
            }
            // Actualizar juego
            $game->update($dataToUpdate);
            // Agregar URL pública de la imagen
            $game->image_url = $game->image ? asset('storage/' . $game->image) : null;

            return response()->json([
                'success' => true,
                'message' => 'Juego actualizado correctamente',
                'data'    => $game,
                'timestamp' => now(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el juego: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    public function deactivate($id)
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

            // Verificar rol admin
            $roles = $user->roles()->pluck('name')->toArray();
            if (!in_array('admin', $roles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para crear Plataformas',
                    'timestamp' => now(),
                ], 403);
            }

            // Buscar el juego
            $game = Game::find($id);
            if (!$game) {
                return response()->json([
                    'success' => false,
                    'message' => 'Juego no encontrado.',
                    'timestamp' => now(),
                ], 404);
            }

            // Si ya está inactivo
            if ($game->status === 'inactive') {
                return response()->json([
                    'success' => false,
                    'message' => 'El juego ya está desactivado.',
                    'timestamp' => now(),
                ], 400);
            }

            // Desactivar
            $game->status = 'inactive';
            $game->save();

            return response()->json([
                'success' => true,
                'message' => 'Juego desactivado correctamente.',
                'data'    => $game,
                'timestamp' => now(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar juego: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }



}
