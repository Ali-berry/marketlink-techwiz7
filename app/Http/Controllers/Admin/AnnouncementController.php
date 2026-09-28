<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Admin\UpdateAnnouncementRequest;
use App\Models\Announcement;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $announcements = Announcement::with('author')
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        return view('admin.announcements.create', ['announcement' => new Announcement(['audience' => 'everyone'])]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Announcement::create([
            'created_by_user_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'audience' => $validated['audience'],
            'published_at' => $this->resolvePublishedAt($validated['action'], null),
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement saved.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validated();

        $announcement->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'audience' => $validated['audience'],
            'published_at' => $this->resolvePublishedAt($validated['action'], $announcement->published_at),
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    private function resolvePublishedAt(string $action, ?Carbon $currentPublishedAt): ?Carbon
    {
        return match ($action) {
            'publish' => $currentPublishedAt ?? now(),
            'unpublish' => null,
            default => $currentPublishedAt,
        };
    }
}
