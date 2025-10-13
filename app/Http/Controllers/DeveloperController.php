<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Developer;
 
class DeveloperController extends Controller
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
                    'message' => 'No tienes permisos para crear Desarrolladores',
                    'timestamp' => now(),
                ], 403);
            }

            // Validar datos
            $validator = \Validator::make($request->all(), [
                'name' => 'required|string|max:100|unique:developers,name',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre es obligatorio, debe ser unico y tener máximo 100 caracteres.',
                    //'errors' => $validator->errors(),
                    'timestamp' => now(),
                ], 400);
            }

            // Crear Desarrollador
            $developer = Developer::create([
                'name' => $request->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Desarrollador creado correctamente',
                'data' => $developer,
                'timestamp' => now(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear Desarrollador: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    public function index()
    {
        try {
            $developer = \App\Models\Developer::all();

            if ($developer->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay Desarrolladores disponibles. Es necesario agregar al menos un desarrollador.',
                'data'    => [],
                'timestamp' => now(),
            ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lista de Desarrolladores obtenida correctamente',
                'data'    => $developer->makeHidden(['created_at','updated_at']),
                'timestamp' => now(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener Desarrolladores: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $developer = \App\Models\Developer::find($id);

            if (!$developer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desarrollador no encontrado',
                    'timestamp' => now(),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Desarrollador obtenido correctamente',
                'data'    => $developer,
                'timestamp' => now(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener al Desarrollador: ' . $e->getMessage(),
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

            // Buscar Desarrollador
            $developer = \App\Models\Developer::find($id);
            if (!$developer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desarrollador no encontrado',
                    'timestamp' => now(),
                ], 404);
            }

            // Validar campos
            $validator = \Validator::make($request->all(), [
                'name' => 'nullable|string|max:100|unique:developers,name,' . $developer->id,
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
            $developer->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => 'Desarrollador actualizado correctamente',
                'data'    => $developer,
                'timestamp' => now(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al editar al Desarrollador: ' . $e->getMessage(),
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

            // Buscar Desarrollador
            $developer = \App\Models\Developer::find($id);
            if (!$developer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desarrollador no encontrado',
                    'timestamp' => now(),
                ], 404);
            }

            // Poner en 0 los juegos relacionados antes de eliminar la categoría
            \App\Models\Game::where('developer_id', $developer->id)
            ->update(['developer_id' => null]);

            // Eliminar Desarrollador
            $developer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Desarrollador eliminado correctamente',
                'timestamp' => now(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar Desarrollador: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

}
