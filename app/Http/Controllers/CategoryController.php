<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Category;

class CategoryController extends Controller
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
                    'message' => 'No tienes permisos para crear categorías',
                    'timestamp' => now(),
                ], 403);
            }

            // Validar datos
            $validator = \Validator::make($request->all(), [
                'name' => 'required|string|max:100|unique:categories,name',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre es obligatorio, debe ser unico y tener máximo 100 caracteres.',
                    //'errors' => $validator->errors(),
                    'timestamp' => now(),
                ], 400);
            }

            // Crear categoría
            $category = Category::create([
                'name' => $request->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada correctamente',
                'data' => $category,
                'timestamp' => now(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear categoría: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    public function index()
    {
        try {
            $categories = \App\Models\Category::all();

            if ($categories->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay categorías disponibles. Es necesario agregar al menos una categoría.',
                'data'    => [],
                'timestamp' => now(),
            ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lista de categorías obtenida correctamente',
                'data'    => $categories->makeHidden(['created_at','updated_at']),
                'timestamp' => now(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorías: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $category = \App\Models\Category::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada',
                    'timestamp' => now(),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Categoría obtenida correctamente',
                'data'    => $category,
                'timestamp' => now(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la categoría: ' . $e->getMessage(),
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

            // Buscar categoría
            $category = \App\Models\Category::find($id);
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada',
                    'timestamp' => now(),
                ], 404);
            }

            // Validar campos
            $validator = \Validator::make($request->all(), [
                'name' => 'nullable|string|max:100|unique:categories,name,' . $category->id,
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
                    'message' => 'Debes enviar al menos un cambio para actualizar la categoría.',
                    'timestamp' => now(),
                ], 400);
            }

            // Aplicar cambios
            $category->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada correctamente',
                'data'    => $category,
                'timestamp' => now(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar categoría: ' . $e->getMessage(),
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

            // Buscar categoría
            $category = \App\Models\Category::find($id);
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada',
                    'timestamp' => now(),
                ], 404);
            }

            // Poner en 0 los juegos relacionados antes de eliminar la categoría
            \App\Models\Game::where('category_id', $category->id)
            ->update(['category_id' => null]);

            // Eliminar categoría
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Categoría eliminada correctamente',
                'timestamp' => now(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar categoría: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }



}
