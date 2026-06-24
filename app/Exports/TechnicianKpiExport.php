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
    protected $techEmails;

    public function __construct(
        $fromDate,
        $toDate,
        array $techEmails = []
    ) {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
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
                $this->techEmails
            )
            ->map(function ($item) {
                // Đúng theo thứ tự dữ liệu cung cấp trong buildRow của TechnicianReportService
                // Phù hợp với headings bên dưới

                return [
                    $item->technician_email,                  // Email nhân viên
                    $item->technician_name,                   // Họ và tên
                    $item->technician_position,               // Vị trí chức danh
                    $item->store_count,                       // Số lượng cửa hàng phụ trách
                    $item->daily_target,                      // Định mức/ngày
                    $item->monthly_target,                    // Định mức/tháng
                    $item->total_completed,                   // Tổng số vụ sửa chữa
                    $item->completion_percent . '%',          // Tỷ lệ hoàn thành/định mức
                    $item->dung_han_count,                    // CV đạt TG + CL (số lượng đúng hạn)
                    $item->late_accepted_count,               // CV chậm TG + đạt CL (số lượng KHÔNG đúng hạn)
                    $item->quality_fail_count,                // CV không đạt (fail quality)
                    $item->total_completed,                   // Số công việc quy đổi (giống tổng số vụ sửa chữa)
                    $item->dung_han_total_percent . '%',      // Tỷ lệ hoàn thành KPI (tỷ lệ đúng hạn / tổng hoàn thành)
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
                    'borderStyle' =>
                        Border::BORDER_THIN,
                ],
            ],
        ]);

        return [];
    }
}