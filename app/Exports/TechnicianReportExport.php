<?php

namespace App\Exports;

use App\Models\MaintenanceRequest;
use App\Models\TechnicianTarget;
use App\Services\TechnicianReportService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TechnicianReportExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithStrictNullComparison
{
    protected Carbon $fromDate;
    protected Carbon $toDate;
    protected ?Carbon $fromDateCompleted = null;
    protected ?Carbon $toDateCompleted = null;
    protected array $techEmails;
    protected $service;

    public function __construct(
        string $fromDate,
        string $toDate,
        ?string $from_date_completed = null, 
        ?string $to_date_completed = null,
        array $techEmails = []
    ) {
        $this->fromDate = Carbon::parse($fromDate)->startOfDay();
        $this->toDate = Carbon::parse($toDate)->endOfDay();

        // Only parse from_date_completed and to_date_completed if they are not null or empty
        $this->fromDateCompleted = !empty($from_date_completed) ? Carbon::parse($from_date_completed)->startOfDay() : null;
        $this->toDateCompleted = !empty($to_date_completed) ? Carbon::parse($to_date_completed)->endOfDay() : null;

        $this->techEmails = collect($techEmails)
            ->filter()
            ->map(fn ($email) => strtolower(trim($email)))
            ->values()
            ->toArray();

        $this->service = app(TechnicianReportService::class);
    }

    public function collection()
    {
        return $this->service
            ->getReport(
                $this->fromDate,
                $this->toDate,
                $this->fromDateCompleted,
                $this->toDateCompleted,
                $this->techEmails
            )
            ->map(function ($item) {

                return [
                    $item->technician_name,

                    $item->store_count,
                    $item->daily_target,
                    $item->monthly_target,

                    $item->total_completed,
                    $item->completion_percent,

                    $item->ngoai_gio_count,

                    $item->dung_han_count,
                    $item->dung_han_dm_percent,
                    $item->dung_han_total_percent,

                    $item->khong_dung_han_count,
                    $item->khong_dung_han_percent,

                    $item->quality_pass_count,
                    $item->quality_pass_dm_percent,
                    $item->quality_pass_total_percent,

                    $item->quality_fail_count,
                    $item->quality_fail_total_percent,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Kỹ thuật viên',
            'Số CH phụ trách',
            'Định mức/ngày',
            'Định mức/tháng',

            'Tổng sự vụ',
            'Tỷ lệ sự vụ/ĐM (%)',

            'SL ngoài giờ',

            'Đúng hạn',
            'Tỷ lệ Đúng hạn/ĐM tháng (%)',
            'Tỷ lệ Đúng hạn/Tổng TH (%)',

            'Trễ hạn',
            'Tỷ lệ Trễ hạn/Tổng TH (%)',

            'Đạt nghiệm thu',
            'Tỷ lệ đạt CL/ĐM tháng (%)',
            'Tỷ lệ đạt CL/Tổng TH (%)',

            'Không đạt nghiệm thu',
            'Tỷ lệ không đạt CL/Tổng TH (%)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->freezePane('A2');

        // Header
        $sheet->getStyle("A1:{$highestColumn}1")
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 13,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'FFD700',
                    ],
                ],
            ]);

        // Căn giữa toàn bộ dữ liệu từ cột B trở đi
        $sheet->getStyle("B2:{$highestColumn}{$highestRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Cột tên kỹ thuật viên căn trái
        $sheet->getStyle("A2:A{$highestRow}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Header căn giữa
        $sheet->getStyle("A1:{$highestColumn}1")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Border
        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => [
                            'rgb' => 'D9D9D9',
                        ],
                    ],
                ],
            ]);

        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
            ->getAlignment()
            ->setWrapText(true);

        return [];
    }
}