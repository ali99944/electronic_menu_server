<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChangelogPoint;
use App\Models\ChangelogVersion; // Import Version model
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;


class ChangelogPointController extends Controller
{
    /**
     * Display a listing of the resource.
     * Optionally filter by version ID.
     */
    public function index(Request $request)
    {
        $query = ChangelogPoint::query();

        if ($request->has('changelog_version_id')) {
            $query->where('changelog_version_id', $request->input('changelog_version_id'));
        }

        $points = $query->with('version:id,version')->latest()->get(); // Get version id/name only

        return response()->json(['data' => $points]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'changelog_version_id' => 'required|integer|exists:changelog_versions,id',
            'type' => ['required', 'string', Rule::in(ChangelogPoint::ALLOWED_TYPES)],
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $point = ChangelogPoint::create($validator->validated());
        $point->load('version:id,version'); // Load relation

        return response()->json(['data' => $point, 'message' => 'Changelog point created successfully.'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ChangelogPoint $changelogPoint) // Route Model Binding
    {
        $changelogPoint->load('version:id,version'); // Load relation
        return response()->json(['data' => $changelogPoint]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChangelogPoint $changelogPoint) // Route Model Binding
    {
         $validator = Validator::make($request->all(), [
             // Allow updating version ID if needed, ensure it exists
            'changelog_version_id' => 'sometimes|required|integer|exists:changelog_versions,id',
            'type' => ['sometimes', 'required', 'string', Rule::in(ChangelogPoint::ALLOWED_TYPES)],
            'description' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $changelogPoint->update($validator->validated());
        $changelogPoint->load('version:id,version'); // Reload relation

        return response()->json(['data' => $changelogPoint, 'message' => 'Changelog point updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChangelogPoint $changelogPoint) // Route Model Binding
    {
        $changelogPoint->delete();
        return response()->json(['message' => 'Changelog point deleted successfully.'], 200); // Or 204 No Content
    }
}
