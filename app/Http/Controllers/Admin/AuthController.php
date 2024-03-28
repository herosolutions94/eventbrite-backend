<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Components\Services\AuthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private $authService;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function loginForm()
    {
        // $password = Hash::make("admin@@");
        // print_r($password);die;
        if (auth()->check()) {
            return $this->authService->redirectAdmin();
        }
        return view('admin.auth.login');
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Email is required',
            'email.email' => 'Email is invalid',
            'password.required' => 'Password is required',
        ]);
        
        return $this->authService->adminLogin($request);
    }
}