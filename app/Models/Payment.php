<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
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

    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function invoice() {
        return $this->belongsTo(Invoice::class);
    }
}
