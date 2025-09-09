<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('company')->check()) {
            return redirect()->route('company.login');
        }

        // Verificar que el company_id en la URL coincida con el del usuario
        $userCompanyId = Auth::guard('company')->user()->company_id;
        $requestCompanyId = $request->get('company_id');
        
        if ($requestCompanyId && (int)$requestCompanyId !== $userCompanyId) {
            abort(403, 'No tienes acceso a los datos de esta empresa.');
        }

        // Si no hay company_id en la request, añadirlo automáticamente
        if (!$requestCompanyId) {
            $request->merge(['company_id' => $userCompanyId]);
        }

        return $next($request);
    }
}