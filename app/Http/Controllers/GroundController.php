<?php

namespace App\Http\Controllers;

use App\Models\Ground;
use Illuminate\Http\Request;

class GroundController extends Controller
{
    public function index()
    {
        return Ground::latest()->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'status' => 'required|in:active,inactive,maintenance'
        ]);

        $ground = Ground::create($validated);
        return response()->json($ground, 201);
    }

    public function show(Ground $ground)
    {
        return $ground;
    }

    public function update(Request $request, Ground $ground)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'status' => 'required|in:active,inactive,maintenance'
        ]);

        $ground->update($validated);
        return response()->json($ground);
    }

    public function destroy(Ground $ground)
    {
        $ground->delete();
        return response()->json(null, 204);
    }
}
