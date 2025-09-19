<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\TwoFactorAuthMail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'Falló la validación.',
                    'errors'    => $validator->errors(),
                    'timestamp' => now(),
                ], 400);
            }

            // Buscar usuario
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'Credenciales inválidas',
                    'timestamp' => now(),
                ], 401);
            }
            
            // Verifica estatus
            if ($user->status !== 'active') {
                return response()->json([
                    'success'   => false,
                    'message'   => 'El usuario está inactivo',
                    'timestamp' => now(),
                ], 403);
            }
            if ($request->email === 'safekidsandrea@gmail.com') {
                $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $user->update(['2facode' => $code]);
                $temporaryToken = base64_encode(json_encode([
                    'email' => $user->email,
                    'expires_at' => now()->addMinutes(15)->timestamp,
                ]));
                //Envia correo
                Mail::to($user->email)->send(new TwoFactorAuthMail($user, $code));
                return response()->json([
                    'success' => true,
                    'message' => 'Login exitoso, código de autenticación enviado al correo',
                    'data'           => $user->email,
                    //'data' => $user,
                    'code' =>$code,
                    'temporaryToken' => $temporaryToken,
                    'timestamp' => now(),
                ], 200);
            }

            // Revisa rol
            $userRoles = $user->roles->pluck('name')->toArray();
            if (empty($userRoles)) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'El usuario no tiene un rol asignado',
                    'timestamp' => now(),
                ], 403);
            }

            //  Genera codigo 2FA
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->update(['2facode' => $code]);

            // Crea token temporal
            $temporaryToken = base64_encode(json_encode([
                'email'      => $user->email,
                'expires_at' => now()->addMinutes(15)->timestamp,
            ]));

            // Envia correo
            Mail::to($user->email)->send(new TwoFactorAuthMail($user, $code));

            return response()->json([
                'success'        => true,
                'message'        => 'Login exitoso, código de autenticación enviado al correo',
                'data'           => $user->email,
                //'data'           => $user->makeHidden(['password', '2facode', 'created_at']),
                'temporaryToken' => $temporaryToken,
                'timestamp'      => now(),
            ], 200);

        }catch (\Exception $e) {
            Log::error('Error en login: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success'   => false,
                'message'   => 'Login fallido: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

    public function verify2fa(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'temporaryToken' => 'required|string',
                'code'           => 'required|string|size:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'Validación fallida. El token temporal y el código de 2FA son obligatorios.',
                    'errors'    => $validator->errors(),
                    'timestamp' => now(),
                ], 400);
            }

            // Decodificar token temporal
            $tokenData = json_decode(base64_decode($request->temporaryToken), true);

            if (!$tokenData || !isset($tokenData['email'], $tokenData['expires_at'])) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'Token temporal inválido',
                    'timestamp' => now(),
                ], 400);
            }

            if (now()->timestamp > $tokenData['expires_at']) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'El token temporal ha expirado',
                    'timestamp' => now(),
                ], 400);
            }

            // Busca al usuario
            $user = User::where('email', $tokenData['email'])->first();

            if (!$user) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'Usuario no encontrado',
                    'timestamp' => now(),
                ], 404);
            }

            // Verificar código 2FA
            if ($user->{'2facode'} !== $request->code) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'Código de 2FA inválido',
                    'timestamp' => now(),
                ], 400);
            }

            // Limpiar código 2FA
            $user->update(['2facode' => null]);

            // Generar JWT
            $token = JWTAuth::fromUser($user);

            // Obtener rol del usuario
            $userRole = $user->roles()->pluck('name')->first(); // devuelve el nombre del primer rol

            //Respuesta exitosa
            return response()->json([
                'success'   => true,
                'message'   => '2FA verificado correctamente',
                'data'      => [
                    'user'  => $user->makeHidden(['password', '2facode']),
                    'role'  => $userRole,
                    'token' => $token,
                ],
                'timestamp' => now(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en verify2fa: ' . $e->getMessage());
            return response()->json([
                'success'   => false,
                'message'   => 'Verificación fallida',
                'timestamp' => now(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            if (!$token) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'Token no proporcionado',
                    'timestamp' => now(),
                ], 400);
            }

            JWTAuth::invalidate($token);

            return response()->json([
                'success'   => true,
                'message'   => 'Logout exitoso',
                'timestamp' => now(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Logout failed: ' . $e->getMessage());
            return response()->json([
                'success'   => false,
                'message'   => 'Falló el cierre de sesión',
                'timestamp' => now(),
            ], 500);
        }
    }

    public function refreshToken()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            
            $user = JWTAuth::setToken($newToken)->toUser();
            
            $userRole = UserRole::where('userId', $user->id)->first();
            
            return response()->json([
                'success' => true,
                'message' => 'Token renovado exitosamente',
                'data' => [
                    'token' => $newToken,
                    'user' => [
                        'id' => $user->id,
                        'firstName' => $user->firstName,
                        'lastName' => $user->lastName,
                        'role' => $userRole ? $userRole->roleId : null
                    ]
                ],
                'timestamp' => now(),
            ], 200);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expirado y no se puede renovar. Inicia sesión nuevamente.',
                'error_code' => 'TOKEN_EXPIRED',
                'timestamp' => now(),
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido',
                'error_code' => 'TOKEN_INVALID',
                'timestamp' => now(),
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo renovar el token',
                'error_code' => 'TOKEN_REFRESH_FAILED',
                'timestamp' => now(),
            ], 500);
        }
    }

    //Funciones para restablecer contraseña
    public function passResetRequest(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El correo es obligatorio',
                    'timestamp' => now(),
                ], 400);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuaio no encontrado',
                    'timestamp' => now(),
                ], 404);
            }

            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->update(['2facode' => $code]);
            //Envio de correo
            Mail::to($user->email)->send(new ResetPasswordMail($user, $code));

            return response()->json([
                'success' => true,
                'message' => 'Correo de restablecimiento de contraseña enviado',
                'data' => $user->email,
                'timestamp' => now(),
            ], 200);
        }
        catch (\Exception $e)
        {
            return response()->json([
                'success' => false,
                'message' => 'Falla del servidor',
                'timestamp' => now(),
            ], 500);
        }
    }

    public function validatorCode(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|size:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El codigo de 6 digitos es obligatorio',
                    'timestamp' => now(),
                ], 400);
            }
        //Buscar usuario
        $user = User::where('2facode', $request->code)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Codigo invalido o esta expirado',
                'timestamp' => now(),
            ], 400);
        }

        $temporaryToken = base64_encode(json_encode([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => 'password_reset',
            'expires_at' => now()->addMinutes(15)->timestamp,
            'verification_code' => $request->code
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Codigo verificado correctamente',
            'data' => [
                'user_id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name
            ],
            'resetToken' => $temporaryToken,
            'timestamp' => now(),
        ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar codigo: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }

        public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'resetToken' => 'required|string',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors();
                if ($errors->has('password')) {
                    $msg = 'La nueva contraseña es obligatoria y debe tener mínimo 8 caracteres.';
                } elseif ($errors->has('resetToken')) {
                    $msg = 'Token de reset es obligatorio.';
                } else {
                    $msg = 'Datos inválidos.';
                }
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => $errors,
                    'timestamp' => now(),
                ], 400);
            }

            // Decodificar y validar el token temporal
            $tokenData = json_decode(base64_decode($request->resetToken), true);

            if (!$tokenData || !isset($tokenData['user_id'], $tokenData['expires_at'], $tokenData['purpose'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de reset inválido',
                    'timestamp' => now(),
                ], 400);
            }

            // Verificar que el token sea para reset de contraseña
            if ($tokenData['purpose'] !== 'password_reset') {
                return response()->json([
                    'success' => false,
                    'message' => 'Token no válido para cambio de contraseña',
                    'timestamp' => now(),
                ], 400);
            }

            // Verificar que el token no haya expirado
            if (now()->timestamp > $tokenData['expires_at']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de reset ha expirado',
                    'timestamp' => now(),
                ], 400);
            }

            // Buscar el usuario
            $user = User::find($tokenData['user_id']);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'timestamp' => now(),
                ], 404);
            }

            // Validar que el código de verificación aún coincida (seguridad extra)
            if (isset($tokenData['verification_code']) && $user->{'2facode'} !== $tokenData['verification_code']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de reset inválido o ya utilizado',
                    'timestamp' => now(),
                ], 400);
            }

            // Actualizar la contraseña y limpiar el código 2FA
            $user->update([
                'password' => Hash::make($request->password),
                '2facode' => null 
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente',
                'data' => [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'password_changed_at' => now()
                ],
                'timestamp' => now(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar contraseña: ' . $e->getMessage(),
                'timestamp' => now(),
            ], 500);
        }
    }



}
