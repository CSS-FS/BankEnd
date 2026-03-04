<?php

namespace App\Exports;

use App\Models\Shed;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ParameterDataExport implements WithEvents, WithTitle
{
    public function __construct(
        protected Shed $shed,
        protected string $parameter,
        protected $logs,
        protected string $unit = '',
        protected array $statistics = [],
        protected ?array $alertThresholds = null,
        protected string $timeRange = '24hour',
        protected string $from = '',
        protected string $to = ''
    ) {}

    public function title(): string
    {
        return ucfirst($this->parameter) . ' Data';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet     = $event->sheet->getDelegate();
                $param     = ucfirst(str_replace('_', ' ', $this->parameter));
                $unitLabel = $this->unit ? " ({$this->unit})" : '';

                // ── Column widths ──────────────────────────────────────────
                $sheet->getColumnDimension('A')->setWidth(24);
                $sheet->getColumnDimension('B')->setWidth(22);
                $sheet->getColumnDimension('C')->setWidth(22);
                $sheet->getColumnDimension('D')->setWidth(22);

                // ══════════════════════════════════════════════════════════
                // ROW 1 — Title
                // ══════════════════════════════════════════════════════════
                $sheet->mergeCells('A1:D1');
                $sheet->setCellValue('A1', $param . ' Data Report');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(40);

                // ══════════════════════════════════════════════════════════
                // ROWS 2–6 — Info Section
                // ══════════════════════════════════════════════════════════
                $infoRows = [
                    2 => ['Shed Name',  $this->shed->name],
                    3 => ['Parameter',  $param . $unitLabel],
                    4 => ['Time Range', ucfirst(str_replace('_', ' ', $this->timeRange))],
                    5 => ['Date Range', $this->from . ' to ' . $this->to],
                    6 => ['Generated',  now()->format('Y-m-d H:i:s')],
                ];

                foreach ($infoRows as $row => [$label, $value]) {
                    $sheet->setCellValue("A{$row}", $label);
                    $sheet->mergeCells("B{$row}:D{$row}");
                    $sheet->setCellValue("B{$row}", $value);

                    $sheet->getStyle("A{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
                    ]);
                    $sheet->getStyle("B{$row}:D{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(20);
                }

                // ══════════════════════════════════════════════════════════
                // ROW 7 — Empty spacer
                // ══════════════════════════════════════════════════════════
                $sheet->mergeCells('A7:D7');
                $sheet->getRowDimension(7)->setRowHeight(10);

                // ══════════════════════════════════════════════════════════
                // ROWS 8–10 — Statistics Summary
                // ══════════════════════════════════════════════════════════
                $sheet->mergeCells('A8:D8');
                $sheet->setCellValue('A8', 'Statistics Summary');
                $sheet->getStyle('A8')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '365314']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                ]);
                $sheet->getRowDimension(8)->setRowHeight(22);

                // Stats labels
                foreach (['A9' => 'Minimum', 'B9' => 'Average', 'C9' => 'Maximum'] as $cell => $label) {
                    $sheet->setCellValue($cell, $label);
                }
                $sheet->getStyle('A9:C9')->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => '4B5563']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFCCB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(9)->setRowHeight(20);

                // Stats values
                $sheet->setCellValue('A10', ($this->statistics['min'] ?? 'N/A') . $unitLabel);
                $sheet->setCellValue('B10', ($this->statistics['average'] ?? 'N/A') . $unitLabel);
                $sheet->setCellValue('C10', ($this->statistics['max'] ?? 'N/A') . $unitLabel);
                $sheet->getStyle('A10:C10')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 13],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFCCB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(10)->setRowHeight(24);

                // ══════════════════════════════════════════════════════════
                // ROWS 11–13 — Alert Thresholds (only if set)
                // ══════════════════════════════════════════════════════════
                $dataHeadingRow = 12; // default when no thresholds

                if ($this->alertThresholds) {
                    $sheet->mergeCells('A11:D11');
                    $sheet->setCellValue('A11', 'Alert Thresholds');
                    $sheet->getStyle('A11')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '78350F']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                    ]);
                    $sheet->getRowDimension(11)->setRowHeight(22);

                    $sheet->setCellValue('A12', 'Low Alert');
                    $sheet->setCellValue('B12', 'High Alert');
                    $sheet->getStyle('A12:B12')->applyFromArray([
                        'font'      => ['bold' => true, 'color' => ['rgb' => '4B5563']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getRowDimension(12)->setRowHeight(20);

                    $sheet->setCellValue('A13', ($this->alertThresholds['low'] ?? 'N/A') . $unitLabel);
                    $sheet->setCellValue('B13', ($this->alertThresholds['high'] ?? 'N/A') . $unitLabel);
                    $sheet->getStyle('A13:B13')->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'DC2626']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getRowDimension(13)->setRowHeight(24);

                    $dataHeadingRow = 15; // push data down
                }

                // ══════════════════════════════════════════════════════════
                // Data table heading row
                // ══════════════════════════════════════════════════════════
                $sheet->setCellValue("A{$dataHeadingRow}", 'Timestamp');
                $sheet->setCellValue("B{$dataHeadingRow}", 'Average' . $unitLabel);
                $sheet->setCellValue("C{$dataHeadingRow}", 'Min' . $unitLabel);
                $sheet->setCellValue("D{$dataHeadingRow}", 'Max' . $unitLabel);
                $sheet->getStyle("A{$dataHeadingRow}:D{$dataHeadingRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B82F6']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($dataHeadingRow)->setRowHeight(26);

                // ══════════════════════════════════════════════════════════
                // Data rows
                // ══════════════════════════════════════════════════════════
                $logs = collect($this->logs)->values();
                foreach ($logs as $i => $log) {
                    $row = $dataHeadingRow + $i + 1;
                    $sheet->setCellValue("A{$row}", $log['timestamp'] ?? 'N/A');
                    $sheet->setCellValue("B{$row}", $log['value'] ?? 0);
                    $sheet->setCellValue("C{$row}", $log['min'] ?? 0);
                    $sheet->setCellValue("D{$row}", $log['max'] ?? 0);

                    $bgColor = ($i % 2 === 0) ? 'FFFFFF' : 'F9FAFB';
                    $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(18);
                }

                // ── Border around data table ──────────────────────────────
                $lastDataRow = $dataHeadingRow + $logs->count();
                $sheet->getStyle("A{$dataHeadingRow}:D{$lastDataRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);

                // ══════════════════════════════════════════════════════════
                // Footer
                // ══════════════════════════════════════════════════════════
                $footerRow = $lastDataRow + 2;
                $sheet->mergeCells("A{$footerRow}:D{$footerRow}");
                $sheet->setCellValue("A{$footerRow}", 'FlockSense IoT Monitoring System | Generated on ' . now()->format('F d, Y'));
                $sheet->getStyle("A{$footerRow}")->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '6B7280']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // No data message
                if ($logs->isEmpty()) {
                    $emptyRow = $dataHeadingRow + 1;
                    $sheet->mergeCells("A{$emptyRow}:D{$emptyRow}");
                    $sheet->setCellValue("A{$emptyRow}", 'No data available for the selected time range.');
                    $sheet->getStyle("A{$emptyRow}")->applyFromArray([
                        'font'      => ['italic' => true, 'color' => ['rgb' => '9CA3AF']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }
            },
        ];
    }
}
