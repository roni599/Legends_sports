<?php

namespace App\Traits;

use App\Models\MonthlyClosing;
use Carbon\Carbon;

trait LocksClosedMonths
{
    public static function bootLocksClosedMonths()
    {
        static::saving(function ($model) {
            $model->checkIfMonthIsLocked($model);
        });

        static::deleting(function ($model) {
            $model->checkIfMonthIsLocked($model);
        });
    }

    protected function checkIfMonthIsLocked($model)
    {
        $dateField = $model->getClosingDateField();
        
        // 1. Check if the new date being saved belongs to a locked month
        if (isset($model->{$dateField})) {
            $date = Carbon::parse($model->{$dateField});
            $monthYear = $date->format('Y-m');
            
            if (MonthlyClosing::where('month_year', $monthYear)->exists()) {
                abort(422, "Action denied. The financial records for {$monthYear} have been locked by the administrator.");
            }
        }

        // 2. Check if an existing record is being modified/moved from an already locked month
        if ($model->exists && $model->isDirty($dateField)) {
            $originalDate = Carbon::parse($model->getOriginal($dateField));
            $originalMonthYear = $originalDate->format('Y-m');
            
            if (MonthlyClosing::where('month_year', $originalMonthYear)->exists()) {
                abort(422, "Action denied. You cannot alter records that originated in a locked month ({$originalMonthYear}).");
            }
        }
    }

    /**
     * Define which date column should be checked for locking.
     * Models can override this if their column is named differently.
     */
    protected function getClosingDateField()
    {
        return 'created_at';
    }
}
