<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        Client::truncate();
        Invoice::truncate();
        Payment::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $admin = User::first();
        $userId = $admin ? $admin->id : 1;

        // 1. Create a few clients
        $clients = [
            ['name' => 'John Doe', 'phone' => '01711000001', 'total_due' => 500],
            ['name' => 'Jane Smith', 'phone' => '01711000002', 'total_due' => -500],
            ['name' => 'Michael Johnson', 'phone' => '01711000003', 'total_due' => 3000],
        ];

        foreach ($clients as $clientData) {
            $client = Client::create($clientData);

            // 2. Create some invoices for the client
            if ($client->total_due > 0) {
                $invoice = Invoice::create([
                    'invoice_number' => 'INV-' . strtoupper(uniqid()),
                    'client_id' => $client->id,
                    'subtotal' => $client->total_due + 1000,
                    'grand_total' => $client->total_due + 1000,
                    'paid' => 1000,
                    'due' => $client->total_due,
                ]);

                // Create initial payment for this invoice
                Payment::create([
                    'client_id' => $client->id,
                    'invoice_id' => $invoice->id,
                    'amount' => 1000,
                    'type' => 'due receive',
                    'payment_method' => 'cash',
                    'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    'user_id' => $userId,
                    'created_at' => now()->subDays(2)
                ]);
            }

            // 3. Create advance if any
            if ($client->total_due < 0) {
                Payment::create([
                    'client_id' => $client->id,
                    'amount' => abs($client->total_due),
                    'type' => 'advance receive',
                    'payment_method' => 'bkash',
                    'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    'user_id' => $userId,
                    'created_at' => now()->subDays(1)
                ]);
            }
        }
        
        // Add one more client with no due
        Client::create(['name' => 'Sarah Connor', 'phone' => '01711000004', 'total_due' => 0]);
        
        echo "Dummy Data created successfully!";
    }
}
