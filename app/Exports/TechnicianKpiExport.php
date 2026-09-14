<?php

namespace App\Exports;

use App\Services\TechnicianReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TechnicianKpiExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithStrictNullComparison
{
    protected $fromDate;
    protected $toDate;
    protected $fromDateCompleted;
    protected $toDateCompleted;
    protected $techEmails;

    public function __construct(
        ?string $fromDate,
        ?string $toDate,
        ?string $fromDateCompleted = null,
        ?string $toDateCompleted = null,
        array $techEmails = []
    ) {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->fromDateCompleted = $fromDateCompleted;
        $this->toDateCompleted = $toDateCompleted;

        $this->techEmails = collect($techEmails)
            ->filter()
            ->map(fn ($email) => strtolower(trim($email)))
            ->values()
            ->toArray();
    }

    public function collection()
    {
        return app(TechnicianReportService::class)
            ->getKpiReport(
                $this->fromDate,
                $this->toDate,
                $this->fromDateCompleted,
                $this->toDateCompleted,
                $this->techEmails
            )
            ->map(function ($item) {
                return [
                    $item->technician_email,
                    $item->technician_name,
                    $item->technician_position,
                    $item->store_count,
                    $item->daily_target,
                    $item->monthly_target,
                    $item->total_completed,
                    $item->completion_percent . '%',
                    $item->dung_han_count,
                    $item->late_accepted_count,
                    $item->quality_fail_count,
                    $item->total_completed,
                    $item->dung_han_total_percent . '%',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Email nhân viên',
            'Họ và tên',
            'Vị trí chức danh',
            'Số lượng cửa hàng phụ trách',
            'Định mức/ngày',
            'Định mức/tháng',
            'Tổng số vụ sửa chữa',
            'Tỷ lệ hoàn thành/định mức (%)',
            'CV đạt TG + CL',
            'CV chậm TG + đạt CL',
            'CV không đạt',
            'Số công việc quy đổi',
            'Tỷ lệ hoàn thành KPI (%)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->freezePane('A2');

        $sheet->getStyle(
            "A1:{$highestColumn}1"
        )->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'D9E2F3',
                ],
            ],
        ]);

        $sheet->getStyle(
            "A1:{$highestColumn}{$highestRow}"
        )->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        return [];
    }
}