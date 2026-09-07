<?php

namespace App\Exports;

use App\Services\TechSystemReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TechSystemKpiExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
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

        /*
        |--------------------------------------------------------------------------
        | Chuẩn hóa email
        |--------------------------------------------------------------------------
        */

        $this->techEmails = array_values(
            array_filter(
                array_map(
                    static fn($email) =>
                    strtolower(trim($email)),
                    $techEmails
                )
            )
        );
    }

    /**
     * Dữ liệu Excel
     *
     * Tổng cộng 20 cột dữ liệu.
     * Không bao gồm STT.
     */
    public function collection()
    {
        $reportService = app(
            TechSystemReportService::class
        );

        $data = $reportService->getKpiReport(
            $this->fromDate,
            $this->toDate,
            $this->techEmails
        );

        return $data->map(
            function ($item) {
                return [
                    /*
                    |--------------------------------------------------------------------------
                    | 1-7: Thông tin nhân viên
                    |--------------------------------------------------------------------------
                    */

                    $item->technician_code,

                    $item->technician_name,

                    $item->technician_position,

                    $item->store_count,

                    $item->daily_target,

                    $item->monthly_target,

                    // Cột 7
                    // = Onsite trong giờ
                    // + Onsite ngoài giờ
                    // + Online trong giờ
                    // + Online ngoài giờ
                    $item->total_completed,

                    /*
                    |--------------------------------------------------------------------------
                    | 8-11: Phân loại công việc
                    |--------------------------------------------------------------------------
                    */

                    // 8. Onsite trong giờ
                    $item->onsite_in_work_count,

                    // 9. Onsite ngoài giờ
                    $item->onsite_off_work_count,

                    // 10. Online trong giờ
                    $item->online_in_work_count,

                    // 11. Online ngoài giờ
                    $item->online_off_work_count,

                    /*
                    |--------------------------------------------------------------------------
                    | 12-13: Tỷ lệ
                    |--------------------------------------------------------------------------
                    */

                    // 12. Tỷ lệ hoàn thành / định mức
                    "{$item->completion_percent}%",

                    // 13. Tỷ lệ hoàn thành / định mức quy đổi
                    "{$item->completion_quy_doi_percent}%",

                    /*
                    |--------------------------------------------------------------------------
                    | 14-18: Các chỉ tiêu KPI
                    |--------------------------------------------------------------------------
                    */

                    // 14. Onsite đạt thời gian + đạt chất lượng
                    $item->onsite_on_time_quality_count,

                    // 15. Onsite chậm thời gian + đạt chất lượng
                    $item->onsite_late_quality_count,

                    // 16. Online trong giờ hoàn thành + đạt chất lượng
                    $item->online_in_work_quality_count,

                    // 17. Online ngoài giờ hoàn thành + đạt chất lượng
                    $item->online_off_work_quality_count,

                    // 18. Không đạt thời gian, chất lượng
                    $item->not_met_count,

                    /*
                    |--------------------------------------------------------------------------
                    | 19-20
                    |--------------------------------------------------------------------------
                    */

                    // 19. Số công việc quy đổi
                    $item->quy_doi_count,

                    // 20. Tỷ lệ hoàn thành KPI
                    "{$item->kpi_percent}%",
                ];
            }
        );
    }

    /**
     * Header Excel
     */
    public function headings(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | 1-7
            |--------------------------------------------------------------------------
            */

            'Mã nhân viên',

            'Họ và tên',

            'Vị trí chức danh',

            'Số lượng cửa hàng phụ trách',

            'Định mức số lượng sửa chữa/ngày',

            'Tổng định mức số lượng sửa chữa/tháng',

            'Tổng số vụ sửa chữa thực hiện trong tháng',

            /*
            |--------------------------------------------------------------------------
            | 8-11
            |--------------------------------------------------------------------------
            */

            'Công việc Onsite thực hiện trong giờ hành chính',

            'Công việc Onsite thực hiện ngoài giờ hành chính',

            'Công việc Online thực hiện trong giờ hành chính',

            'Công việc Online ngoài giờ hoàn thành',

            /*
            |--------------------------------------------------------------------------
            | 12-13
            |--------------------------------------------------------------------------
            */

            'Tỷ lệ hoàn thành/định mức',

            'Tỷ lệ hoàn thành/định mức quy đổi',

            /*
            |--------------------------------------------------------------------------
            | 14-18
            |--------------------------------------------------------------------------
            */

            'Tổng số CV Onsite đạt thời gian, đạt chất lượng',

            'Tổng số CV Onsite chậm thời gian, đạt chất lượng',

            'Tổng số CV Online trong giờ hoàn thành, đạt chất lượng',

            'Tổng số CV Online ngoài giờ hoàn thành, đạt chất lượng',

            'Tổng số CV không đạt thời gian, chất lượng',

            /*
            |--------------------------------------------------------------------------
            | 19-20
            |--------------------------------------------------------------------------
            */

            'Số công việc quy đổi',

            'Tỷ lệ hoàn thành KPI',
        ];
    }

    /**
     * Style Excel
     */
    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        /*
        |--------------------------------------------------------------------------
        | Freeze + AutoFilter
        |--------------------------------------------------------------------------
        */

        $sheet->freezePane('A2');

        if ($highestRow > 1) {
            $sheet->setAutoFilter(
                "A1:T{$highestRow}"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle('A1:T1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => [
                    'rgb' => '262626',
                ],
            ],

            'fill' => [
                'fillType' => Fill::FILL_SOLID,

                'startColor' => [
                    'rgb' => 'D9EAF7',
                ],
            ],

            'alignment' => [
                'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,

                'vertical' =>
                    Alignment::VERTICAL_CENTER,

                'wrapText' => true,
            ],

            'borders' => [
                'allBorders' => [
                    'borderStyle' =>
                        Border::BORDER_THIN,

                    'color' => [
                        'rgb' => 'B7B7B7',
                    ],
                ],
            ],
        ]);

        $sheet
            ->getRowDimension(1)
            ->setRowHeight(65);

        /*
        |--------------------------------------------------------------------------
        | Body
        |--------------------------------------------------------------------------
        */

        if ($highestRow >= 2) {
            $sheet
                ->getStyle(
                    "A2:T{$highestRow}"
                )
                ->applyFromArray([
                    'alignment' => [
                        'vertical' =>
                            Alignment::VERTICAL_CENTER,
                    ],

                    'borders' => [
                        'allBorders' => [
                            'borderStyle' =>
                                Border::BORDER_THIN,

                            'color' => [
                                'rgb' => 'D9D9D9',
                            ],
                        ],
                    ],
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Column widths
        |--------------------------------------------------------------------------
        */

        $widths = [
            // 1-7: Thông tin nhân viên

            'A' => 15, // Mã NV

            'B' => 24, // Họ tên

            'C' => 22, // Vị trí

            'D' => 18, // Số cửa hàng

            'E' => 20, // Định mức ngày

            'F' => 22, // Định mức tháng

            'G' => 24, // Tổng số vụ

            // 8-11: Phân loại

            'H' => 24, // Onsite trong giờ

            'I' => 25, // Onsite ngoài giờ

            'J' => 25, // Online trong giờ

            'K' => 25, // Online ngoài giờ

            // 12-13: Tỷ lệ

            'L' => 20, // HT/ĐM

            'M' => 25, // HT/ĐM quy đổi

            // 14-18: KPI

            'N' => 28, // Onsite đạt TG + CL

            'O' => 29, // Onsite trễ + CL

            'P' => 31, // Online trong giờ + CL

            'Q' => 32, // Online ngoài giờ + CL

            'R' => 30, // Không đạt TG, CL

            // 19-20

            'S' => 22, // Số việc quy đổi

            'T' => 22, // KPI
        ];

        foreach ($widths as $column => $width) {
            $sheet
                ->getColumnDimension($column)
                ->setWidth($width);
        }

        /*
        |--------------------------------------------------------------------------
        | Alignment
        |--------------------------------------------------------------------------
        */

        // Thông tin nhân viên
        $sheet
            ->getStyle(
                "A2:C{$highestRow}"
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_LEFT
            );

        // Số lượng cửa hàng + định mức + phân loại
        $sheet
            ->getStyle(
                "D2:K{$highestRow}"
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        // Các cột KPI
        $sheet
            ->getStyle(
                "L2:T{$highestRow}"
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        /*
        |--------------------------------------------------------------------------
        | Percentage columns
        |--------------------------------------------------------------------------
        |
        | Service đã trả về giá trị dạng:
        |
        | 104.17
        |
        | collection() chuyển thành:
        |
        | "104.17%"
        |
        | Vì vậy không dùng number format 0.00%.
        |--------------------------------------------------------------------------
        */

        return [];
    }
}