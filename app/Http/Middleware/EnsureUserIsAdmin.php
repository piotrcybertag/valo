<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            abort(403, 'Dostęp tylko dla zalogowanych administratorów.');
        }

        $typ = strtoupper(trim(Auth::user()->typ ?? ''));
        if ($typ !== 'ADM') {
            abort(403, 'Dostęp tylko dla użytkowników typu ADM.');
        }

        return $next($request);
    }
}
