<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->string('filter')->toString();

        $notifications = $request->user()
            ->notifications()
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? '',
                'summary' => $notification->data['summary'] ?? '',
                'url' => $notification->data['url'] ?? '#',
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at->diffForHumans(),
                'created_at_raw' => $notification->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Admin/Pages/Notifications/Index', [
            'notifications' => $notifications,
            'filter' => $filter,
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $notification)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()
            ->update(['read_at' => now()]);

        return back();
    }
}
