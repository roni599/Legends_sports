<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory, \App\Traits\LocksClosedMonths;

    protected $guarded = ["id"];

    protected function getClosingDateField()
    {
        return 'date';
    }
}
