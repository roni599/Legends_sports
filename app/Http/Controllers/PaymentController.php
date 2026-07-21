<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function show(Payment $payment)
    {
        $payment->load('client');
        return response()->json([
            'payment' => $payment,
            'client' => $payment->client
        ]);
    }
}
