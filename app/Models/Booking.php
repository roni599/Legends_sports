<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use \App\Traits\LocksClosedMonths;

    protected $guarded = ['id'];

    protected function getClosingDateField()
    {
        return 'created_at';
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
