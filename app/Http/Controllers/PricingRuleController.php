<?php

namespace App\Http\Controllers;

use App\Models\PricingRule;
use Illuminate\Http\Request;

class PricingRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = PricingRule::with('ground')->latest();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhereHas('ground', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
        }
        
        return $query->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ground_id' => 'nullable|exists:grounds,id',
            'type' => 'required|in:peak_hour,weekend,tournament',
            'start_time' => 'nullable|required_if:type,peak_hour|date_format:H:i',
            'end_time' => 'nullable|required_if:type,peak_hour|date_format:H:i',
            'price_modifier' => 'required|numeric',
            'status' => 'required|in:active,inactive'
        ]);

        // Business Logic: Prevent overlapping peak_hour rules for the same ground
        if ($validated['type'] === 'peak_hour') {
            $overlapping = PricingRule::where('type', 'peak_hour')
                ->where('status', 'active')
                ->where(function($q) use ($validated) {
                    $q->where('ground_id', $validated['ground_id'])
                      ->orWhereNull('ground_id');
                })
                ->where(function ($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                      ->where('end_time', '>', $validated['start_time']);
                })
                ->exists();

            if ($overlapping) {
                return response()->json([
                    'message' => 'An active peak hour rule already exists for this time period. Overlapping peak rules are not allowed as they cause double-charging.'
                ], 422);
            }
        }

        $rule = PricingRule::create($validated);
        return response()->json($rule, 201);
    }

    public function show(PricingRule $pricingRule)
    {
        return $pricingRule;
    }

    public function update(Request $request, PricingRule $pricingRule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ground_id' => 'nullable|exists:grounds,id',
            'type' => 'required|in:peak_hour,weekend,tournament',
            'start_time' => 'nullable|required_if:type,peak_hour|date_format:H:i',
            'end_time' => 'nullable|required_if:type,peak_hour|date_format:H:i',
            'price_modifier' => 'required|numeric',
            'status' => 'required|in:active,inactive'
        ]);

        // Business Logic: Prevent overlapping peak_hour rules for the same ground
        if ($validated['type'] === 'peak_hour') {
            $overlapping = PricingRule::where('type', 'peak_hour')
                ->where('id', '!=', $pricingRule->id)
                ->where('status', 'active')
                ->where(function($q) use ($validated) {
                    $q->where('ground_id', $validated['ground_id'])
                      ->orWhereNull('ground_id');
                })
                ->where(function ($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                      ->where('end_time', '>', $validated['start_time']);
                })
                ->exists();

            if ($overlapping) {
                return response()->json([
                    'message' => 'An active peak hour rule already exists for this time period. Overlapping peak rules are not allowed as they cause double-charging.'
                ], 422);
            }
        }

        $pricingRule->update($validated);
        return response()->json($pricingRule);
    }

    public function toggleStatus(PricingRule $pricingRule)
    {
        $pricingRule->update([
            'status' => $pricingRule->status === 'active' ? 'inactive' : 'active'
        ]);
        return response()->json($pricingRule);
    }

    public function destroy(PricingRule $pricingRule)
    {
        $pricingRule->delete();
        return response()->json(null, 204);
    }
}
