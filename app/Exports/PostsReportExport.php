<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PostsReportExport implements FromCollection, WithHeadings
{

    public function __construct(
        public Collection $postsHistogram,
    ) {
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->postsHistogram->map(fn($row) => [
            $row['date'],
            (string) $row['count'],
        ]);
    }

    public function headings(): array
    {
        return [
            'Date',
            'Count',
        ];
    }
}
