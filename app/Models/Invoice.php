<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use \App\Traits\LocksClosedMonths;

    protected $guarded = ['id'];

    protected function getClosingDateField()
    {
        return 'created_at';
    }

    public function booking() {
        return $this->belongsTo(Booking::class);
    }

    public function client() {
        return $this->belongsTo(Client::class);
    }

    public function sales() {
        return $this->hasMany(Sale::class);
    }
}
