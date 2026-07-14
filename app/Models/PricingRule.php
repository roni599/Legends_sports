<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    use HasFactory;

    protected $guarded = ["id"];

    public function ground()
    {
        return $this->belongsTo(Ground::class);
    }
}
