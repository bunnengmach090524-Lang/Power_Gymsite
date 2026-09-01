<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            abort(403, 'Your account is not linked to a gym.');
        }

        if ($user->tenant->subscription_status === 'expired') {
            return redirect()->route('billing.expired');
        }

        return $next($request);
    }
}