<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Booking extends Model
{
    use \App\Traits\LocksClosedMonths;

    protected $guarded = ['id'];

    protected function getClosingDateField()
    {
        return 'created_at';
    }

    public static function completeExpired()
    {
        $now = Carbon::now();

        $bookings = Booking::whereIn('status', ['pending', 'confirmed', 'running'])
            ->with('slots')
            ->get();

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
            } elseif ($hasStarted && in_array($booking->status, ['pending', 'confirmed'])) {
                $booking->update(['status' => 'running']);
            }
        }
    }

    public function client() {
        return $this->belongsTo(Client::class);
    }

    public function ground() {
        return $this->belongsTo(Ground::class);
    }

    public function slots() {
        return $this->hasMany(BookingSlot::class);
    }

    public function invoice() {
        return $this->hasOne(Invoice::class);
    }
}
