<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clients
        $clients = [
            [
                'id' => 1,
                'name' => 'Roni',
                'phone' => '01760109383',
                'email' => 'ekroni99@gmail.com',
                'address' => 'Uttara, Dhaka',
                'total_due' => '0.00',
                'status' => 'active',
                'created_at' => '2026-07-23 15:31:10',
                'updated_at' => '2026-07-23 15:33:33'
            ],
            [
                'id' => 2,
                'name' => 'Babu',
                'phone' => '01516525356',
                'email' => 'Babu@gmail.com',
                'address' => 'Mohakhali, Dhaka',
                'total_due' => '0.00',
                'status' => 'active',
                'created_at' => '2026-07-23 15:31:46',
                'updated_at' => '2026-07-23 15:31:46'
            ]
        ];

        foreach ($clients as $client) {
            \App\Models\Client::updateOrCreate(['id' => $client['id']], $client);
        }

        // Grounds
        $grounds = [
            [
                'id' => 1,
                'name' => 'Park - 1',
                'description' => null,
                'location' => 'Southest Corner',
                'base_price_per_hour' => '0.00',
                'status' => 'active',
                'created_at' => '2026-07-23 15:27:45',
                'updated_at' => '2026-07-23 15:27:45'
            ],
            [
                'id' => 2,
                'name' => 'Park - 2',
                'description' => null,
                'location' => 'Westest Corner',
                'base_price_per_hour' => '0.00',
                'status' => 'active',
                'created_at' => '2026-07-23 15:28:06',
                'updated_at' => '2026-07-23 15:28:06'
            ]
        ];

        foreach ($grounds as $ground) {
            \App\Models\Ground::updateOrCreate(['id' => $ground['id']], $ground);
        }

        // Pricing Rules
        $pricingRules = [
            [
                'id' => 1,
                'name' => 'Friday Weekend',
                'ground_id' => 2,
                'type' => 'weekend',
                'start_time' => '00:00:00',
                'end_time' => '11:59:00',
                'price_modifier' => '4000.00',
                'status' => 'active',
                'created_at' => '2026-07-23 15:29:20',
                'updated_at' => '2026-07-23 15:29:20'
            ],
            [
                'id' => 2,
                'name' => 'Peak Hour',
                'ground_id' => 1,
                'type' => 'peak_hour',
                'start_time' => '00:00:00',
                'end_time' => '23:59:00',
                'price_modifier' => '3000.00',
                'status' => 'active',
                'created_at' => '2026-07-23 15:30:14',
                'updated_at' => '2026-07-23 15:30:14'
            ]
        ];

        foreach ($pricingRules as $rule) {
            \App\Models\PricingRule::updateOrCreate(['id' => $rule['id']], $rule);
        }
    }
}
