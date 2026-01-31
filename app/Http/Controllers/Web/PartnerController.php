<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Partner::with('media')
            ->ordered();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Filter by keyword
        if ($request->has('keyword') && $request->keyword) {
            $query->byKeyword($request->keyword);
        }

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $partners = $request->has('per_page')
            ? $query->paginate($request->per_page)
            : $query->get();

        return view(
            'admin.partners.index',
            compact('partners')
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $partner = null;

        return view('admin.partners.create', compact('partner'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'url' => 'nullable|url',
            'introduction' => 'nullable|string',
            'partnership_detail' => 'nullable|string',
            'support_keywords' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'logo' => 'nullable|mimes:jpeg,jpg,png,svg|max:2000',
        ]);

        // Parse support_keywords if it's a string (from tagsinput)
        if (isset($validated['support_keywords']) && is_string($validated['support_keywords'])) {
            $keywords = json_decode($validated['support_keywords'], true);
            $validated['support_keywords'] = is_array($keywords) ? $keywords : [];
        }

        // Set defaults
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $partner = Partner::create($validated);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $partner->addMedia($file);
        }

        return redirect()
            ->route('partners.index')
            ->with('success', 'Partner added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Partner $partner)
    {
        $partner->load('media');

        return response()->json($partner);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Partner $partner)
    {
        return view('admin.partners.create', compact('partner'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'url' => 'nullable|url',
            'introduction' => 'nullable|string',
            'partnership_detail' => 'nullable|string',
            'support_keywords' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'logo' => 'nullable|mimes:jpeg,jpg,png,svg|max:2000',
        ]);

        // Parse support_keywords if it's a string (from tagsinput)
        if (isset($validated['support_keywords']) && is_string($validated['support_keywords'])) {
            $keywords = json_decode($validated['support_keywords'], true);
            $validated['support_keywords'] = is_array($keywords) ? $keywords : [];
        }

        // Set defaults
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $validated['sort_order'] = $validated['sort_order'] ?? $partner->sort_order;

        $partner->update($validated);

        // Handle logo upload (replace existing)
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');

            // Delete old media if exists
            if ($partner->media->first()) {
                $partner->deleteMedia($partner->media->first()->id);
            }

            $partner->addMedia($file);
        }

        return redirect()
            ->route('partners.index')
            ->with('success', 'Partner updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Partner $partner)
    {
        // Delete logo file if exists
        $media = $partner->media()->first();

        if ($media) {
            $partner->deleteMedia($media->id);
        }

        $partner->delete();

        return redirect()
            ->route('partners.index')
            ->with('success', 'Partner is deleted successfully.');
    }

    /**
     * Add keyword to partner
     */
    public function addKeyword(Partner $partner, Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:50',
        ]);

        $partner->addKeyword($request->keyword);
        $partner->save();

        return redirect()
            ->route('partners.index')
            ->with('success', 'Keyword added successfully.');
    }

    /**
     * Remove keyword from partner
     */
    public function removeKeyword(Partner $partner, Request $request)
    {
        $request->validate([
            'keyword' => 'required|string',
        ]);

        $partner->removeKeyword($request->keyword);
        $partner->save();

        return redirect()
            ->route('partners.index')
            ->with('success', 'Keyword removed successfully.');
    }

    /**
     * Get all unique keywords across partners
     */
    public function getAllKeywords()
    {
        $keywords = Partner::active()
            ->pluck('support_keywords')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $keywords,
        ]);
    }

    public function toggleStatus(Partner $partner, Request $request)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $partner->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'success' => true,
            'is_active' => $partner->is_active,
        ]);
    }

    public function updateSort(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'sort_order' => 'required|integer|min:0|max:9999',
        ]);

        $partner->sort_order = $validated['sort_order'];
        $partner->save();

        return response()->json([
            'success' => true,
            'sort_order' => $partner->sort_order,
        ]);
    }
}
