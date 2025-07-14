<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(Request $request): Response
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'csrf_token' => 'required|string',
        ]);

        $login = trim(strip_tags($request->input('login')));
        $password = $request->input('password');
        $csrfToken = $request->input('csrf_token');

        if (!session()->has('csrf_token') || session('csrf_token') !== $csrfToken) {
            Log::error('Неверный CSRF-токен', [
                'session_csrf' => session('csrf_token', 'не установлен'),
                'request_csrf' => $csrfToken
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Неверный CSRF-токен',
                'debug' => [
                    'session_csrf' => session('csrf_token', 'не установлен'),
                    'request_csrf' => $csrfToken
                ]
            ], 403);
        }

        try {
            $user = User::where('login', $login)
                        ->orWhere('mail', $login)
                        ->first();

            if (!$user) {
                Log::error("Пользователь не найден: $login");
                return response()->json([
                    'success' => false,
                    'error' => 'Неверный логин или пароль',
                    'debug' => "Пользователь не найден: $login"
                ], 401);
            }

            if (!Auth::attempt(['login' => $login, 'password' => $password]) &&
                !Auth::attempt(['mail' => $login, 'password' => $password])) {
                Log::error("Неверный пароль для: $login");
                return response()->json([
                    'success' => false,
                    'error' => 'Неверный логин или пароль',
                    'debug' => 'password_verify вернул false'
                ], 401);
            }

            $user = Auth::user();
            session()->forget('csrf_token');

            return response()->json([
                'success' => true,
                'message' => 'Авторизация успешна',
                'user' => [
                    'id' => $user->id,
                    'login' => $user->login,
                    'mail' => $user->mail
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Ошибка авторизации: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Ошибка сервера',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    public function register(Request $request): Response
    {
        $request->validate([
            'login' => 'required|string',
            'mail' => 'required|email',
            'password' => 'required|string|min:8',
            'csrf_token' => 'required|string',
        ]);

        $login = trim(strip_tags($request->input('login')));
        $mail = filter_var(trim($request->input('mail')), FILTER_VALIDATE_EMAIL);
        $password = $request->input('password');
        $csrfToken = $request->input('csrf_token');

        if (!$mail) {
            return response()->json([
                'success' => false,
                'error' => 'Некорректный email'
            ], 400);
        }

        if (!session()->has('csrf_token') || session('csrf_token') !== $csrfToken) {
            Log::error('Неверный CSRF-токен при регистрации', [
                'session_csrf' => session('csrf_token', 'не установлен'),
                'request_csrf' => $csrfToken
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Неверный CSRF-токен',
                'debug' => [
                    'session_csrf' => session('csrf_token', 'не установлен'),
                    'request_csrf' => $csrfToken
                ]
            ], 403);
        }

        try {
            Log::info('Проверяем занятость логина/почты...', ['login' => $login, 'mail' => $mail]);
            $existingUser = User::where('login', $login)->orWhere('mail', $mail)->first();

            if ($existingUser) {
                Log::warning('Логин или email заняты', ['login' => $login, 'mail' => $mail]);
                return response()->json([
                    'success' => false,
                    'error' => 'Логин или email заняты'
                ], 400);
            }

            $hash = password_hash($password, PASSWORD_ARGON2ID);
            Log::info('Хэш пароля создан');

            $user = User::create([
                'login' => $login,
                'mail' => $mail,
                'password_hash' => $hash,
            ]);

            session()->forget('csrf_token');
            Log::info('Регистрация успешна', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Регистрация успешна!'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Ошибка регистрации: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Ошибка сервера',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}