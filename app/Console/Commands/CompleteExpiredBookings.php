<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use Carbon\Carbon;

class CompleteExpiredBookings extends Command
{
    protected $signature = 'bookings:complete-expired';
    protected $description = 'Automatically mark bookings as completed when their slot end time has passed';

    public function handle()
    {
        $now = Carbon::now();

        $bookings = Booking::whereIn('status', ['pending', 'confirmed', 'running'])
            ->with('slots')
            ->get();

        $count = 0;

        foreach ($bookings as $booking) {
            $hasStarted = false;
            $allExpired = true;

            foreach ($booking->slots as $slot) {
                $slotStart = Carbon::parse($slot->date . ' ' . $slot->start_time);
                $slotEnd = Carbon::parse($slot->date . ' ' . $slot->end_time);
                if ($slotEnd->lte($slotStart)) $slotEnd->addDay();

                if ($now->gte($slotStart)) $hasStarted = true;
                if ($slotEnd->isFuture()) $allExpired = false;
            }

            if ($allExpired && $booking->status === 'running') {
                $booking->update(['status' => 'completed']);
                $count++;
            } elseif ($hasStarted && in_array($booking->status, ['pending', 'confirmed'])) {
                $booking->update(['status' => 'running']);
                $count++;
            }
        }

        if ($count > 0) {
            $this->info("Auto-completed {$count} booking(s).");
        }

        return Command::SUCCESS;
    }
}
