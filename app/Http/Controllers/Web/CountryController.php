<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $countries = Country::orderBy('country')->get();

        return view('admin.countries.index', compact('countries'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        Country::create($data);

        return redirect()
            ->route('countries.index')
            ->with('success', 'Country added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Country $country)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Country $country)
    {
        $data = $this->validated($request, $country->id);

        $country->update($data);

        return redirect()
            ->route('countries.index')
            ->with('success', 'Country updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        $country->delete();

        return redirect()
            ->route('countries.index')
            ->with('success', 'Country deleted successfully.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'country' => [
                'required', 'string', 'max:150',
                Rule::unique('countries', 'country')->ignore($ignoreId),
            ],
            'alpha_2_code' => [
                'required', 'string', 'size:2',
                Rule::unique('countries', 'alpha_2_code')->ignore($ignoreId),
            ],
            'alpha_3_code' => [
                'required', 'string', 'size:3',
                Rule::unique('countries', 'alpha_3_code')->ignore($ignoreId),
            ],
        ], [], [
            'country' => 'country',
            'alpha_2_code' => 'alpha-2 code',
            'alpha_3_code' => 'alpha-3 code',
        ]);
    }
}
