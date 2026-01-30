<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NotificationTopic;

class TopicController extends Controller
{
    public function index()
    {
        $topics = NotificationTopic::orderBy('title')->get();

        return view('admin.topics.index', compact('topics'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191|regex:/^[a-z0-9\-\_\.]+$/|unique:notification_topics,name',
            'title' => 'required|string|max:191',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        NotificationTopic::create($data);

        return back()->with('success', 'Topic created.');
    }

    public function update(Request $request, NotificationTopic $topic)
    {
        $data = $request->validate([
            'title' => 'required|string|max:191',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $topic->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'Topic updated.');
    }
}
