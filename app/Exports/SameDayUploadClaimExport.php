<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Facades\DB;

class SameDayUploadClaimExport implements FromQuery, WithHeadings, WithTitle
{
    protected $table;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function query()
    {
        return DB::table($this->table)
            ->select(
                "{$this->table}.CrBy",
                DB::raw("ROUND(COUNT(*) / NULLIF(SUM(1), 0), 2) as Ratio")
            )
            ->whereRaw("DATE(BillDate) = DATE(CrDate)")
            ->groupBy("{$this->table}.CrBy")
            ->orderBy("{$this->table}.CrBy");
    }

    public function headings(): array
    {
        return ['Created By', 'Ratio'];
    }

    public function title(): string
    {
        return 'Same Day Upload';
    }
}
