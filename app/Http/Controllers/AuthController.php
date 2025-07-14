<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registro de usuario
     * POST /api/register
     */
    public function register(Request $request)
    {
        try {
            // Validación de datos
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed', // confirmed busca password_confirmation
                'colonia' => 'nullable|string|max:255',
                'municipio' => 'nullable|string|max:255',
            ]);

            // Crear el usuario
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'colonia' => $request->colonia,
                'municipio' => $request->municipio,
                'rating' => 0.0, // Rating inicial
            ]);

            // Crear token de autenticación
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Usuario registrado exitosamente',
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login de usuario
     * POST /api/login
     */
    public function login(Request $request)
    {
        try {
            // Validación de datos
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            // Verificar credenciales
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            // Eliminar tokens anteriores (opcional)
            $user->tokens()->delete();

            // Crear nuevo token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login exitoso',
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver perfil del usuario autenticado
     * GET /api/profile
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'message' => 'Perfil obtenido exitosamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'colonia' => $user->colonia,
                    'municipio' => $user->municipio,
                    'rating' => $user->rating,
                    'full_location' => $user->full_location,
                    'days_in_app' => $user->days_in_app,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar perfil del usuario
     * PUT /api/profile/update
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            // Validación de datos
            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'sometimes|required|string|min:8|confirmed',
                'colonia' => 'sometimes|nullable|string|max:255',
                'municipio' => 'sometimes|nullable|string|max:255',
            ]);

            // Actualizar solo los campos enviados
            $updateData = [];

            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }

            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }

            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            if ($request->has('colonia')) {
                $updateData['colonia'] = $request->colonia;
            }

            if ($request->has('municipio')) {
                $updateData['municipio'] = $request->municipio;
            }

            // Actualizar el usuario
            $user->update($updateData);

            return response()->json([
                'message' => 'Perfil actualizado exitosamente',
                'user' => $user->fresh() // Obtener datos actualizados
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout del usuario
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        try {
            // Eliminar el token actual
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Logout exitoso'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
