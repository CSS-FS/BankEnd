<?php

namespace App\Exports;

use App\Models\Shed;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParameterDataExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected Shed $shed;
    protected string $parameter;
    protected $logs;
    protected string $unit;

    public function __construct(Shed $shed, string $parameter, $logs, string $unit = '')
    {
        $this->shed = $shed;
        $this->parameter = $parameter;
        $this->logs = $logs;
        $this->unit = $unit;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->logs)->map(function ($log) {
            return [
                'timestamp' => $log['timestamp'] ?? 'N/A',
                'value' => $log['value'] ?? 0,
                'min' => $log['min'] ?? 0,
                'max' => $log['max'] ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        $parameterLabel = ucfirst(str_replace('_', ' ', $this->parameter));
        $unitLabel = $this->unit ? " ({$this->unit})" : '';

        return [
            'Timestamp',
            $parameterLabel . $unitLabel . ' (Avg)',
            $parameterLabel . $unitLabel . ' (Min)',
            $parameterLabel . $unitLabel . ' (Max)',
        ];
    }

    public function title(): string
    {
        return ucfirst($this->parameter) . ' Data';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
