<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CompanyAuthController extends Controller
{
    /**
     * Mostrar formulario de login de empresa
     */
    public function showLoginForm()
    {
        if (Auth::guard('company')->check()) {
            return redirect()->route('company.dashboard', ['company_id' => Auth::guard('company')->user()->company_id]);
        }
        
        return view('auth.company.login');
    }

    /**
     * Procesar login de empresa
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials = $request->only('email', 'password');
        $credentials['is_active'] = true; // Solo usuarios activos
        $remember = $request->boolean('remember');

        if (Auth::guard('company')->attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // Actualizar último login
            Auth::guard('company')->user()->updateLastLogin();
            
            $companyId = Auth::guard('company')->user()->company_id;
            
            return redirect()->intended(route('company.dashboard', ['company_id' => $companyId]));
        }

        throw ValidationException::withMessages([
            'email' => ['Las credenciales proporcionadas no coinciden con nuestros registros o su cuenta está inactiva.'],
        ]);
    }

    /**
     * Logout de empresa
     */
    public function logout(Request $request)
    {
        Auth::guard('company')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('company.login');
    }
}