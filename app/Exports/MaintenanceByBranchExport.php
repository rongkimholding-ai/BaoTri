<?php

namespace App\Exports;

use App\Models\MaintenanceRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class MaintenanceByBranchExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithEvents,
    ShouldAutoSize
{
    protected $fromDate;
    protected $toDate;

    protected Collection $stores;

    public function __construct($fromDate, $toDate)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;

        $this->stores = collect(
            json_decode(
                file_get_contents(resource_path('json/stores.json')),
                true
            )
        )->keyBy('code');
    }

    public function collection()
    {
        return MaintenanceRequest::query()
            ->select(
                'branch_code',
                'branch_name'
            )
            ->selectRaw('COUNT(*) as total_requests')
            ->selectRaw("
                SUM(
                    CASE
                        WHEN sla_status = '".config('sla_status.code.COMPLETED')."'
                        THEN 1
                        ELSE 0
                    END
                ) as ontime_requests
            ")
            ->selectRaw("
                SUM(
                    CASE
                        WHEN sla_status <> '".config('sla_status.code.COMPLETED')."'
                        THEN 1
                        ELSE 0
                    END
                ) as overdue_requests
            ")
            ->whereBetween(
                DB::raw('DATE(request_date)'),
                [
                    $this->fromDate,
                    $this->toDate
                ]
            )
            ->groupBy(
                'branch_code',
                'branch_name'
            )
            ->orderBy('branch_code')
            ->get();
    }

    public function headings(): array
    {
        return [[
            'Mã cơ sở',
            'Tên cơ sở',
            'AM',
            'OM',
            'Số lượng yêu cầu sửa chữa phát sinh',
            'Số lượng yêu cầu được xử lý đúng hạn',
            'Số lượng yêu cầu không được xử lý đúng hạn',
        ]];
    }

    public function map($row): array
    {
        $store = $this->stores[$row->branch_code] ?? null;

        return [
            $row->branch_code,
            $row->branch_name,
            $store['am_name'] ?? '',
            $store['om_name'] ?? '',
            $row->total_requests,
            $row->ontime_requests,
            $row->overdue_requests,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                /*
                |--------------------------------------------------------------------------
                | Thêm dòng tiêu đề
                |--------------------------------------------------------------------------
                */
                $sheet->insertNewRowBefore(1, 1);

                $title =
                    'THỐNG KÊ SỬA CHỮA BẢO TRÌ CƠ SỞ TỪ '
                    . date('d/m/Y', strtotime($this->fromDate))
                    . ' ĐẾN '
                    . date('d/m/Y', strtotime($this->toDate));

                $sheet->mergeCells('A1:G1');
                $sheet->setCellValue('A1', $title);

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                /*
                |--------------------------------------------------------------------------
                | Freeze Pane
                |--------------------------------------------------------------------------
                | Dòng 1: Tiêu đề
                | Dòng 2: Header
                */
                $sheet->freezePane('A3');

                /*
                |--------------------------------------------------------------------------
                | Style tiêu đề
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A1:G1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => [
                            'rgb' => '000000'
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'FFD700'
                        ],
                    ],
                ]);

                /*
                |--------------------------------------------------------------------------
                | Style Header
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A2:G2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => [
                            'rgb' => '000000'
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'D9EAD3'
                        ],
                    ],
                ]);

                /*
                |--------------------------------------------------------------------------
                | Border toàn bảng
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle(
                    "A1:{$highestColumn}{$highestRow}"
                )->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => [
                                'rgb' => '000000'
                            ],
                        ],
                    ],
                ]);

                /*
                |--------------------------------------------------------------------------
                | Border ngoài đậm hơn
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle(
                    "A1:{$highestColumn}{$highestRow}"
                )->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_MEDIUM);

                /*
                |--------------------------------------------------------------------------
                | Auto row height
                |--------------------------------------------------------------------------
                */
                foreach (range(1, $highestRow) as $row) {
                    $sheet->getRowDimension($row)
                        ->setRowHeight(-1);
                }

                /*
                |--------------------------------------------------------------------------
                | Căn giữa header
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle('A2:G2')
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    );

                /*
                |--------------------------------------------------------------------------
                | Căn giữa cột số
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle("E3:G{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    );

                /*
                |--------------------------------------------------------------------------
                | Căn giữa dọc toàn bảng
                |--------------------------------------------------------------------------
                */
                $sheet->getStyle(
                    "A1:{$highestColumn}{$highestRow}"
                )->getAlignment()
                    ->setVertical(
                        Alignment::VERTICAL_CENTER
                    );

                /*
                |--------------------------------------------------------------------------
                | Chiều cao dòng
                |--------------------------------------------------------------------------
                */
                $sheet->getRowDimension(1)
                    ->setRowHeight(28);

                $sheet->getRowDimension(2)
                    ->setRowHeight(40);

                /*
                |--------------------------------------------------------------------------
                | Width cố định đẹp hơn AutoSize
                |--------------------------------------------------------------------------
                */
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(40);
                $sheet->getColumnDimension('C')->setWidth(25);
                $sheet->getColumnDimension('D')->setWidth(25);
                $sheet->getColumnDimension('E')->setWidth(18);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(18);
            },
        ];
    }
}