<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NotificationTopic;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    /**
     * @return Factory|View|\Illuminate\View\View
     */
    public function index()
    {
        $topics = NotificationTopic::orderBy('title')
            ->get();
        return view('admin.push_notifications.topics', compact('topics'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
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

        return back()->with('success', 'Topic created successfully.');
    }

    /**
     * @param Request $request
     * @param NotificationTopic $topic
     * @return RedirectResponse
     */
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

        return back()->with('success', 'Topic updated successfully.');
    }
}
