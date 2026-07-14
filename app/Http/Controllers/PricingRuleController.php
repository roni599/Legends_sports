<?php

namespace App\Http\Controllers;

use App\Models\PricingRule;
use Illuminate\Http\Request;

class PricingRuleController extends Controller
{
    public function index()
    {
        return PricingRule::with('ground')->latest()->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ground_id' => 'nullable|exists:grounds,id',
            'type' => 'required|in:peak_hour,weekend,tournament',
            'start_time' => 'nullable|required_if:type,peak_hour|date_format:H:i',
            'end_time' => 'nullable|required_if:type,peak_hour|date_format:H:i',
            'price_modifier' => 'required|numeric'
        ]);

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
            'price_modifier' => 'required|numeric'
        ]);

        $pricingRule->update($validated);
        return response()->json($pricingRule);
    }

    public function destroy(PricingRule $pricingRule)
    {
        $pricingRule->delete();
        return response()->json(null, 204);
    }
}
