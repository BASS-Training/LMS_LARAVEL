<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CourseParticipantsExport implements FromQuery, WithMapping, WithHeadings, WithStyles, WithColumnWidths
{
    private int $rowNumber = 0;

    public function __construct(
        protected Builder $query,
        protected string $programType
    ) {}

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['No', 'Nama', 'Email', 'Kelas', 'Tanggal Enroll Course', 'Tanggal Enroll Kelas', 'Status Kelas', 'Program'];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $row->name,
            $row->email,
            $row->class_name ?? 'Belum ditetapkan',
            $row->course_enrolled_at
                ? Carbon::parse($row->course_enrolled_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i')
                : '-',
            $row->class_enrolled_at
                ? Carbon::parse($row->class_enrolled_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i')
                : '-',
            match ($row->class_status ?? '') {
                'active'    => 'Aktif',
                'upcoming'  => 'Mendatang',
                'completed' => 'Selesai',
                default     => '-',
            },
            match ($this->programType) {
                'avpn_ai' => 'AVPN AI',
                default   => 'Regular',
            },
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E40AF'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 30,
            'C' => 35,
            'D' => 25,
            'E' => 22,
            'F' => 22,
            'G' => 15,
            'H' => 15,
        ];
    }
}
