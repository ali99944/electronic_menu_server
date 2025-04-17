<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ChangelogVersion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ChangelogVersionController extends Controller
{
    /**
     * Display a listing of the resource.
     * Sorted newest first based on creation date.
     */
    public function index()
    {
        // Eager load points for efficiency, order by creation date descending
        $versions = ChangelogVersion::with('points')->get();
        return response()->json(['data' => $versions]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'version' => 'required|string|max:255|unique:changelog_versions,version',
            'release_date' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $version = ChangelogVersion::create($validator->validated());

        // Optionally load points if needed immediately (usually empty on create)
        $version->load('points');

        return response()->json(['data' => $version, 'message' => 'Version created successfully.'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ChangelogVersion $changelogVersion) // Route Model Binding
    {
        // Eager load points
        $changelogVersion->load('points');
        return response()->json(['data' => $changelogVersion]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChangelogVersion $changelogVersion) // Route Model Binding
    {
         $validator = Validator::make($request->all(), [
            // Ignore current record ID when checking uniqueness
            'version' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('changelog_versions')->ignore($changelogVersion->id)],
            'release_date' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $changelogVersion->update($validator->validated());

        // Optionally reload points if they might change via other means
        $changelogVersion->load('points');

        return response()->json(['data' => $changelogVersion, 'message' => 'Version updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChangelogVersion $changelogVersion) // Route Model Binding
    {
        // Points will be deleted automatically due to cascade constraint
        $changelogVersion->delete();

        return response()->json(['message' => 'Version deleted successfully.'], 200); // Or 204 No Content
    }
}
