<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyClosing extends Model
{
    protected $fillable = ['month_year', 'closed_by'];

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
