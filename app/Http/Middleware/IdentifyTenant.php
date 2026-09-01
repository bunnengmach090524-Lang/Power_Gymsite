<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');

        $tenant = Tenant::where('slug', $slug)->first();

        if (! $tenant) {
            abort(404, 'Gym not found.');
        }

        if ($tenant->subscription_status === 'expired') {
            abort(403, 'This gym\'s website is currently unavailable.');
        }

        $request->attributes->set('tenant', $tenant);

        inertia()->share('currentTenant', [
            'name' => $tenant->name,
            'slug' => $tenant->slug,
        ]);

        return $next($request);
    }
}