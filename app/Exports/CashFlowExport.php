<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class CashFlowExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        //
    }
}
