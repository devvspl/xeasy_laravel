<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Facades\DB;
use App\Exports\ClaimReportExport;

class MonthWiseClaimReportExport implements WithMultipleSheets
{
    protected $query;
    protected $filters;
    protected $columns;
    protected $protectSheets;
    protected $table;

    public function __construct($query, array $filters, array $columns, bool $protectSheets = false, $table)
    {
        $this->query = $query;
        $this->filters = $filters;
        $this->columns = $columns;
        $this->protectSheets = $protectSheets;
        $this->table = $table;
    }

    public function sheets(): array
    {
        $sheets = [];

        $monthMap = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        // Get distinct months from base table directly (not from aliased query)
        $months = DB::table($this->table)
            ->selectRaw('DISTINCT ClaimMonth')
            ->pluck('ClaimMonth')
            ->filter()
            ->toArray();

        if (empty($months)) {
            $sheets[] = new ClaimReportExport(
                $this->query,
                $this->filters,
                $this->columns,
                'No Data',
                $this->protectSheets,
                $this->table
            );
            return $sheets;
        }

        foreach ($months as $month) {
            // Add month filter to the base query, use table name directly instead of alias
            $monthQuery = (clone $this->query)->where("{$this->table}.ClaimMonth", $month);

            // Count unique ExpId values correctly without alias
            $count = (clone $monthQuery)->count(DB::raw("DISTINCT {$this->table}.ExpId"));

            if ($count > 0) {
                $monthFilters = array_merge($this->filters, ['months' => [$month]]);
                $sheetName = $monthMap[$month] ?? 'Month ' . $month;

                $sheets[] = new ClaimReportExport(
                    $monthQuery,
                    $monthFilters,
                    $this->columns,
                    $sheetName,
                    $this->protectSheets,
                    $this->table
                );
            }
        }

        // If no records matched, add a no data sheet
        if (empty($sheets)) {
            $sheets[] = new ClaimReportExport(
                $this->query,
                $this->filters,
                $this->columns,
                'No Data',
                $this->protectSheets,
                $this->table
            );
        }

        return $sheets;
    }
}
