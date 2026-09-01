<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $query = Notification::where('tenant_id', $tenantId)->latest();

        if ($request->status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($request->status === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return inertia('Admin/Notification/Notification', [
            'notifications' => $query->paginate(15)->withQueryString(),
            'unreadCount' => Notification::where('tenant_id', $tenantId)->unread()->count(),
            'types' => Notification::where('tenant_id', $tenantId)->distinct()->pluck('type'),
            'filters' => ['status' => $request->query('status'), 'type' => $request->query('type')],
        ]);
    }

    public function markRead(Notification $notification)
    {
        $notification->update(['read_at' => now()]);

        return back();
    }

    public function markAllRead()
    {
        Notification::unread()->update(['read_at' => now()]);

        return back();
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return back();
    }
}