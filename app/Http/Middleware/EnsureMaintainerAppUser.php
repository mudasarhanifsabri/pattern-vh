<?php

namespace App\Http\Middleware;

use App\Models\OperationsTeamMember;
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

        $member = OperationsTeamMember::query()
            ->where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if (! $member) {
            return response()->view('maintainer.setup-required', ['user' => $user], 403);
        }

        return $next($request);
    }
}
