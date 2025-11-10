<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class UserController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function registerAdmin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'emailCreator' => 'required|string|email|max:100',
                'passwordCreator'   => 'required|string|min:8',
                'first_name' => 'required|string|max:100',
                'last_name'  => 'required|string|max:100',
                'phone'      => 'nullable|digits:10',
                'email'      => 'required|string|email|max:100|unique:users,email',
                'password'   => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                if ($errors->has('first_name')) {
                    $msg = 'El nombre es obligatorio y debe tener máximo 100 caracteres.';
                } elseif ($errors->has('last_name')) {
                    $msg = 'El apellido es obligatorio y debe tener máximo 100 caracteres.';
                } elseif ($errors->has('phone')) {
                    $msg = 'El teléfono es obligatorio y debe tener máximo 10 dígitos.';
                } elseif ($errors->has('email')) {
                    $msg = 'El correo es obligatorio, debe ser válido y único.';
                } elseif ($errors->has('password')) {
                    $msg = 'La contraseña es obligatoria y debe tener mínimo 8 caracteres.';
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

            // Buscar al creador por su email
            $creatorUser = User::where('email', $request->emailCreator)->first();

            if (!$creatorUser || !\Hash::check($request->passwordCreator, $creatorUser->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales del creador inválidas.',
                    'timestamp' => now(),
                ], 401);
            }

            $creatorId = $creatorUser->id;

            if ($creatorId == 1) {
                // Buscar rol admin
                $adminRole = Role::where('name', 'admin')->first();
                if (!$adminRole) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No hay rol para asignar en la base de datos.',
                        'timestamp' => now(),
                    ], 400);
                }

                // Crear usuario
                $user = User::create([
                    'first_name' => $request->first_name,
                    'last_name'  => $request->last_name,
                    'phone'      => $request->phone,
                    'email'      => $request->email,
                    'password'   => \Hash::make($request->password),
                    'status'     => 'active',
                ]);

                // Asignar rol admin
                $user->roles()->attach($adminRole->id);

                return response()->json([
                    'success'   => true,
                    'message'   => 'Usuario creado correctamente y se le asignó el rol: ' . $adminRole->name,
                    'data'      => [
                        'createdUser' => $user->makeHidden(['password', '2facode', 'created_at']),
                    ],
                    'timestamp' => now()
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario creador no tiene permisos para registrar admins.',
                    'timestamp' => now(),
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server failed: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    public function registerVisit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:100',
                'last_name'  => 'required|string|max:100',
                'phone'      => 'nullable|digits:10',
                'email'      => 'required|string|email|max:100|unique:users,email',
                'password'   => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                if ($errors->has('first_name')) {
                    $msg = 'El nombre es obligatorio y debe tener máximo 100 caracteres.';
                } elseif ($errors->has('last_name')) {
                    $msg = 'El apellido es obligatorio y debe tener máximo 100 caracteres.';
                } elseif ($errors->has('phone')) {
                    $msg = 'El teléfono es obligatorio y debe tener máximo 10 dígitos.';
                } elseif ($errors->has('email')) {
                    $msg = 'El correo es obligatorio, debe ser válido y único.';
                } elseif ($errors->has('password')) {
                    $msg = 'La contraseña es obligatoria y debe tener mínimo 8 caracteres.';
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

            // Buscar rol visitante
            $visitorRole = Role::where('name', 'visitor')->first();
            if (!$visitorRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay rol para asignar en la base de datos.',
                    'timestamp' => now(),
                ], 400);
            }

            // Crear usuario visitante
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'phone'      => $request->phone,
                'email'      => $request->email,
                'password'   => \Hash::make($request->password),
                'status'     => 'active',
            ]);

            // Asignar rol visitante
            $user->roles()->attach($visitorRole->id);

            return response()->json([
                'success'   => true,
                'message'   => 'Usuario creado correctamente y se le asignó el rol: ' . $visitorRole->name,
                'data'      => [
                'createdUser' => $user->makeHidden(['password', '2facode', 'created_at']),
                ],
                'timestamp' => now()
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server failed: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    //Acciones de los usuarios en sus perfiles
    public function myProfile()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado en el token',
                    'timestamp' => now(),
                ], 401);
            }

            // Obtener roles del usuario
            $roles = $user->roles;
            if ($roles->isEmpty()) {
                // Cualquier otro usuario sin rol sí se bloquea
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no tiene rol asignado',
                    'timestamp' => now(),
                ], 403);
            }

            // Si el usuario tiene roles, devolverlos
            return response()->json([
                'success' => true,
                'message' => 'Perfil del usuario obtenido exitosamente',
                'data' => $user->makeHidden(['password', '2facode', 'created_at','roles']),
                'role_info' => $roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                }),
                'timestamp' => now(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener perfil: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }
    
    public function updateProfile(Request $request, $id)
    {
        try {

            // Validamos al editor
            $EditorUser = JWTAuth::parseToken()->authenticate();
            if (!$EditorUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado en el token',
                    'timestamp' => now(),
                ], 401);
            }

            // Verificamos que el usuario del token sea el mismo que se quiere editar
            if ($EditorUser->id != $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para realizar esta accion',
                    'timestamp' => now(),
                ], 403);
            }

            $user = \App\Models\User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'timestamp' => now(),
                ], 404);
            }

            // Validación manual para capturar errores
            $validator = \Validator::make($request->all(), [
                'first_name' => 'nullable|string|max:100',
                'last_name'  => 'nullable|string|max:100',
                'phone'      => 'nullable|digits:10',
                'email'      => 'nullable|email|max:100|unique:users,email,' . $user->id,
                'password'   => 'nullable|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                if ($errors->has('first_name')) {
                    $msg = 'El nombre debe tener máximo 100 caracteres.';
                } elseif ($errors->has('last_name')) {
                    $msg = 'El apellido debe tener máximo 100 caracteres.';
                } elseif ($errors->has('phone')) {
                    $msg = 'El teléfono debe tener máximo 10 dígitos.';
                } elseif ($errors->has('email')) {
                    $msg = 'El correo debe ser válido y único.';
                } elseif ($errors->has('password')) {
                    $msg = 'La contraseña debe tener mínimo 8 caracteres.';
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
                                ->filter(fn($v) => $v !== null && $v !== '')
                                ->toArray();

            if (empty($dataToUpdate)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes enviar al menos un campo para actualizar.',
                ], 400);
            }

            if (isset($dataToUpdate['password'])) {
                $dataToUpdate['password'] = bcrypt($dataToUpdate['password']);
            }

            $user->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado correctamente.',
                'data'    => $user->makeHidden(['password', '2facode']),
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar perfil: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    public function deactivateAccount(Request $request, $id)
    {
        try {
            // Obtener usuario desde token
            $editorUser = JWTAuth::parseToken()->authenticate();
            if (!$editorUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado en el token',
                    'timestamp' => now(),
                ], 401);
            }

            // Validar que el usuario solo pueda desactivar su propia cuenta
            if ($editorUser->id != $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para realizar esta accion',
                    'timestamp' => now(),
                ], 403);
            }

            // Buscar el usuario
            $user = \App\Models\User::find($id);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'timestamp' => now(),
                ], 404);
            }

            // Desactivar la cuenta
            $user->status = 'inactive'; // o 0 si manejas status como boolean
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Cuenta desactivada correctamente.',
                'timestamp' => now(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar la cuenta: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }







}
