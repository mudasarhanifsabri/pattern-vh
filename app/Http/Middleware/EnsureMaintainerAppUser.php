<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureMaintainerAppUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isMaintainerAppUser()) {
            return response()->view('maintainer.setup-required', ['user' => $user], 403);
        }

        return $next($request);
    }
}
