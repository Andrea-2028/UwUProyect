<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Platform;

class PlatformController extends Controller
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

            // Validar datos
            $validator = \Validator::make($request->all(), [
                'name' => 'required|string|max:100|unique:platforms,name',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre es obligatorio, debe ser unico y tener máximo 100 caracteres.',
                    //'errors' => $validator->errors(),
                    'timestamp' => now(),
                ], 400);
            }

            // Crear Plataformas
            $platform = Platform::create([
                'name' => $request->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Plataforma creado correctamente',
                'data' => $platform,
                'timestamp' => now(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear Plataforma: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    public function index()
    {
        try {
            $platform = \App\Models\Platform::all();

            if ($platform->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay Plataformas disponibles. Es necesario agregar al menos una Plataforma.',
                'data'    => [],
                'timestamp' => now(),
            ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lista de Plataformas obtenida correctamente',
                'data'    => $platform->makeHidden(['created_at','updated_at']),
                'timestamp' => now(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener Plataforma: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $platform = \App\Models\Platform::find($id);

            if (!$platform) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plataforma no encontrada',
                    'timestamp' => now(),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Plataforma obtenido correctamente',
                'data'    => $platform,
                'timestamp' => now(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la Plataforma: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Validar usuario desde token
            $editor = \Tymon\JWTAuth\Facades\JWTAuth::parseToken()->authenticate();
            if (!$editor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado en el token',
                    'timestamp' => now(),
                ], 401);
            }

            // Verificar rol admin
            $roles = $editor->roles()->pluck('name')->toArray();
            if (!in_array('admin', $roles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los administradores pueden realizar esta accion',
                    'timestamp' => now(),
                ], 403);
            }

            // Buscar Plataforma
            $platform = \App\Models\Platform::find($id);
            if (!$platform) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plataforma no encontrada',
                    'timestamp' => now(),
                ], 404);
            }

            // Validar campos
            $validator = \Validator::make($request->all(), [
                'name' => 'nullable|string|max:100|unique:platforms,name,' . $platform->id,
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                $msg = $errors->has('name')
                    ? 'El nombre debe ser válido, único y no mayor a 100 caracteres.'
                    : 'Datos inválidos.';

                return response()->json([
                    'success'   => false,
                    'message'   => $msg,
                    'errors'    => $errors,
                    'timestamp' => now(),
                ], 400);
            }

            // Filtrar solo cambios reales (ignorar null y "")
            $dataToUpdate = collect($request->only(['name']))
                ->filter(fn($value) => $value !== null && $value !== '')
                ->toArray();

            if (empty($dataToUpdate)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes enviar al menos un cambio para actualizar los datos',
                    'timestamp' => now(),
                ], 400);
            }

            // Aplicar cambios
            $platform->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => 'Plataforma actualizada correctamente',
                'data'    => $platform,
                'timestamp' => now(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al editar la Plataforma: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // Obtener usuario del token
            $user = \Tymon\JWTAuth\Facades\JWTAuth::parseToken()->authenticate();
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
                    'message' => 'Solo los administradores pueden realizar esta accion',
                    'timestamp' => now(),
                ], 403);
            }

            // Buscar Plataformas
            $platform = \App\Models\Platform::find($id);
            if (!$platform) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plataforma no encontrada',
                    'timestamp' => now(),
                ], 404);
            }

            // Eliminar Plataformas
            $platform->delete();

            return response()->json([
                'success' => true,
                'message' => 'Plataforma eliminada correctamente',
                'timestamp' => now(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar Plataforma: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }
}
