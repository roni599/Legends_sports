<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $guarded = ['id'];

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
