<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class NotificationController extends ApiController
{
    public function index(Request $request)
    {
        $notifications = QueryBuilder::for(Notification::class)
            ->where('user_id', Auth::id())
            ->with(['farm', 'notifiable'])
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::exact('is_read'),
                AllowedFilter::scope('created_between', 'createdBetween'),
            ])
            ->allowedSorts(['created_at', 'is_read'])
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Marked as read']);
    }

    public function unreadCount()
    {
        $count = Notification::where('user_id', Auth::id())->where('is_read', false)->count();

        return response()->json(['unread_count' => $count]);
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['message' => 'All marked as read']);
    }
}
