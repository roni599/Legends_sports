<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = ['id'];

    public function client() {
        return $this->belongsTo(Client::class);
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }
}
