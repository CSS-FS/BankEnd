<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display all notifications
     */
    public function index()
    {
        if (Auth::user()->hasRole('admin')) {
            $notifications = Notification::with(['farm', 'notifiable', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        } else {
            $notifications = Notification::where('user_id', Auth::id())
                ->with(['farm', 'notifiable', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        }

        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead(Notification $notification)
    {
        // Ensure the notification belongs to the authenticated user
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        return back()->with('success', 'Notification marked as read.');
    }
}
