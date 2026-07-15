<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::latest();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
        }
        
        return $query->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:clients,phone',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'total_due' => 'nullable|numeric'
        ]);

        $validated['total_due'] = $validated['total_due'] ?? 0;

        $client = Client::create($validated);
        return response()->json($client, 201);
    }

    public function show(Client $client)
    {
        return $client;
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:clients,phone,' . $client->id,
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'total_due' => 'nullable|numeric'
        ]);

        $validated['total_due'] = $validated['total_due'] ?? 0;

        $client->update($validated);
        return response()->json($client);
    }

    public function destroy(Client $client)
    {
        if (\App\Models\Booking::where('client_id', $client->id)->exists()) {
            return response()->json([
                'message' => 'Cannot delete this client because they have a booking history. Deleting them would result in financial data loss.'
            ], 422);
        }

        $client->delete();
        return response()->json(null, 204);
    }
}
