<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NotificationOutbox;
use App\Models\NotificationTopic;

class PushNotificationController extends Controller
{
    public function enqueue(Request $request)
    {
        $data = $request->validate([
            'target' => 'required|in:topic,user,token',
            'topic' => 'nullable|string|max:191',
            'user_id' => 'nullable|integer|exists:users,id',
            'device_token_id' => 'nullable|integer|exists:device_tokens,id',
            'title' => 'nullable|string|max:191',
            'body' => 'nullable|string|max:2000',
            'payload' => 'nullable|json',
            'scheduled_at' => 'nullable|date',
        ]);

        $payload = $data['payload'] ? json_decode($data['payload'], true) : null;

        $outbox = new NotificationOutbox;
        $outbox->target_type = $data['target'];
        $outbox->title = $data['title'] ?? null;
        $outbox->body = $data['body'] ?? null;
        $outbox->data = $payload;
        $outbox->scheduled_at = $data['scheduled_at'] ?? now();

        if ($data['target'] === 'topic') {
            // validate topic exists & active
            $topic = NotificationTopic::where('name', $data['topic'] ?? '')->where('is_active', true)->firstOrFail();
            $outbox->target_topic = $topic->name;
        } elseif ($data['target'] === 'user') {
            $outbox->target_id = $data['user_id'];
        } else {
            $outbox->target_id = $data['device_token_id'];
        }

        $outbox->save();

        return back()->with('success', 'Notification queued.');
    }

    public function logs()
    {
        $items = NotificationOutbox::orderByDesc('id')->paginate(40);

        return view('admin.notifications.logs', compact('items'));
    }
}
