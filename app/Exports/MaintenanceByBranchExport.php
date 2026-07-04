<?php

namespace App\Exports;

use App\Models\MaintenanceRequest;
use App\Models\Store;
use Carbon\Carbon;
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
    protected $fromDateCompleted;
    protected $toDateCompleted;
    protected $techEmails;

    /**
     * Map: branch_code(string) => Store
     */
    protected Collection $storesByCode;

    /**
     * Map: normalized_branch_name(string) => Store
     * (chỉ lấy store đầu tiên theo branch_name chuẩn hóa về lower-case + gộp space, loại bỏ null key)
     */
    protected Collection $storesByName;

    public function __construct($fromDate, $toDate, $fromDateCompleted, $toDateCompleted, array $techEmails = [])
    {
        $this->fromDate = $fromDate ? Carbon::parse($fromDate)->startOfDay() : null;
        $this->toDate = $toDate ? Carbon::parse($toDate)->endOfDay() : null;
        $this->fromDateCompleted = $fromDateCompleted ? Carbon::parse($fromDateCompleted)->startOfDay() : null;
        $this->toDateCompleted = $toDateCompleted ? Carbon::parse($toDateCompleted)->endOfDay() : null;
        $this->techEmails = $techEmails;

        $stores = Store::all();

        $storesByCode = [];
        $storesByName = [];

        foreach ($stores as $store) {
            // Map by branch_code if code is not null/empty
            $code = trim((string) $store->code);
            if ($code !== '' && $code !== null) {
                $storesByCode[$code] = $store;
            }

            // Map by normalized branch_name (trim, lower, collapse multi-spaces), skip if null or empty
            if (isset($store->name) && trim($store->name) !== '') {
                $normalizedName = preg_replace('/\s+/', ' ', mb_strtolower(trim($store->name)));
                // Only store the first occurrence (usually enough for reporting by display name, can be changed to array if want all matches)
                if (!isset($storesByName[$normalizedName])) {
                    $storesByName[$normalizedName] = $store;
                }
            }
        }
        $this->storesByCode = collect($storesByCode);
        $this->storesByName = collect($storesByName);
    }

    public function collection()
    {
        $query = MaintenanceRequest::query()
            ->select(
                'branch_code',
                'branch_name'
            )
            ->selectRaw('COUNT(*) as total_requests')
            ->selectRaw("
                SUM(
                    CASE
                        WHEN sla_status = '" . config('sla_status.code.COMPLETED') . "'
                        THEN 1
                        ELSE 0
                    END
                ) as ontime_requests
            ")
            ->selectRaw("
                SUM(
                    CASE
                        WHEN sla_status <> '" . config('sla_status.code.COMPLETED') . "'
                        THEN 1
                        ELSE 0
                    END
                ) as overdue_requests
            ");

        if ($this->fromDate !== null && $this->toDate !== null) {
            $query->whereBetween(
                DB::raw('DATE(request_date)'),
                [
                    $this->fromDate,
                    $this->toDate
                ]
            );
        }

        if ($this->fromDateCompleted !== null && $this->toDateCompleted !== null) {
            $query->whereBetween(
                DB::raw('DATE(actual_completion_date)'),
                [
                    $this->fromDateCompleted,
                    $this->toDateCompleted
                ]);
        }

        if (!empty($this->techEmails)) {
            $query->whereIn(
                'technician_email',
                $this->techEmails
            );
        }

        return $query->groupBy(
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
        $store = null;

        // Ưu tiên ánh xạ theo branch_code nếu có (không null, không rỗng)
        $code = trim((string) $row->branch_code);
        if ($code !== '' && $code !== null) {
            $store = $this->storesByCode[$code] ?? null;
        }

        // Nếu không tìm thấy bằng branch_code, thử với normalized branch_name
        if (!$store) {
            $normalizedBranchName = preg_replace('/\s+/', ' ', mb_strtolower(trim($row->branch_name)));
            $store = $this->storesByName[$normalizedBranchName] ?? null;
        }

        // Chuẩn hóa extract được trường am_name, om_name
        $am = '';
        $om = '';
        if ($store) {
            if (is_object($store)) {
                $am = $store->am_name ?? '';
                $om = $store->om_name ?? '';
            } elseif (is_array($store)) { // fallback if for any reason it's array
                $am = $store['am_name'] ?? '';
                $om = $store['om_name'] ?? '';
            }
        }

        return [
            $row->branch_code,
            $row->branch_name,
            $am,
            $om,
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

                // Thêm dòng tiêu đề đầu
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

                // Freeze Pane
                $sheet->freezePane('A3');

                // Style tiêu đề
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

                // Style Header
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

                // Border toàn bảng
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

                // Border ngoài đậm hơn
                $sheet->getStyle(
                    "A1:{$highestColumn}{$highestRow}"
                )->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_MEDIUM);

                // Auto row height
                foreach (range(1, $highestRow) as $rowNum) {
                    $sheet->getRowDimension($rowNum)
                        ->setRowHeight(-1);
                }

                // Căn giữa header
                $sheet->getStyle('A2:G2')
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    );

                // Căn giữa các cột số
                $sheet->getStyle("E3:G{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    );

                // Căn giữa dọc toàn bảng
                $sheet->getStyle(
                    "A1:{$highestColumn}{$highestRow}"
                )->getAlignment()
                    ->setVertical(
                        Alignment::VERTICAL_CENTER
                    );

                // Chiều cao dòng
                $sheet->getRowDimension(1)
                    ->setRowHeight(28);

                $sheet->getRowDimension(2)
                    ->setRowHeight(40);

                // Đặt chiều rộng cột cố định
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