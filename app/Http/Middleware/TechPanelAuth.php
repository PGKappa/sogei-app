<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Basic auth per il pannello tecnico di manutenzione.
 * Credenziali in .env: TECH_PANEL_USER / TECH_PANEL_PASSWORD. Se non configurate il pannello è disabilitato (404).
 */
class TechPanelAuth
{
    public function handle(Request $request, Closure $next)
    {
        $user = (string) config('maintenance.panel_user');
        $password = (string) config('maintenance.panel_password');

        if ($user === '' || $password === '') {
            abort(404);
        }

        $okUser = hash_equals($user, (string) $request->getUser());
        $okPassword = hash_equals($password, (string) $request->getPassword());

        if (!($okUser && $okPassword)) {
            return response('Autenticazione richiesta', 401, ['WWW-Authenticate' => 'Basic realm="Tecnico"']);
        }

        return $next($request);
    }
}
