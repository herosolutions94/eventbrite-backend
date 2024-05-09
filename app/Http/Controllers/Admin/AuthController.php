<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Components\Services\AuthService;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    private $authService;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function loginForm()
    {
        // if (auth()->check()) {
        //     return $this->authService->redirectAdmin();
        // }
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
    public function logout(){
        if(Session()->has("adminLoginId")){
            Session::pull('adminLoginId');
            return redirect("admin/login");
        }
        // auth()->logout();

    }
}
