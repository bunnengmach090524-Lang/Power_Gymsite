<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;

class QuickSearchController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $q = trim($request->query('q', ''));

        if (! $user || ! $user->tenant_id || mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return Member::where('tenant_id', $user->tenant_id)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->limit(8)
            ->get(['id', 'name', 'phone']);
    }
}