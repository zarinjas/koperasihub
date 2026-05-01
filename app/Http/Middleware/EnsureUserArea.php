<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserArea
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $area): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route($area.'.login'));
        }

        if ($area === 'member' && ! $user->isMember()) {
            return redirect()->route('admin.dashboard');
        }

        if ($area === 'admin' && ! $user->isAdmin()) {
            return redirect()->route('member.dashboard');
        }

        return $next($request);
    }
}
