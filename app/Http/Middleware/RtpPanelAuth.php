<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Basic auth per il pannello RTP. Credenziali in .env: RTP_PANEL_USER / RTP_PANEL_PASSWORD.
 * Se non configurate il pannello è disabilitato (404).
 */
class RtpPanelAuth
{
    public function handle(Request $request, Closure $next)
    {
        $user = (string) config('rtp.panel_user');
        $password = (string) config('rtp.panel_password');

        if ($user === '' || $password === '') {
            abort(404);
        }

        $okUser = hash_equals($user, (string) $request->getUser());
        $okPassword = hash_equals($password, (string) $request->getPassword());

        if (!($okUser && $okPassword)) {
            return response('Autenticazione richiesta', 401, ['WWW-Authenticate' => 'Basic realm="RTP"']);
        }

        return $next($request);
    }
}
