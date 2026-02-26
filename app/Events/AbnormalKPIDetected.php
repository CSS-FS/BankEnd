<?php

namespace App\Events;

use App\Models\Flock;
use App\Models\ProductionLog;
use App\Models\Shed;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AbnormalKPIDetected
{
    use Dispatchable, SerializesModels;

    public ProductionLog $productionLog;
    public Shed $shed;
    public Flock $flock;
    public array $breaches;

    /**
     * @param array $breaches  Each breach: ['kpi', 'label', 'value', 'threshold', 'severity', 'unit']
     */
    public function __construct(ProductionLog $productionLog, Shed $shed, Flock $flock, array $breaches)
    {
        $this->productionLog = $productionLog;
        $this->shed          = $shed;
        $this->flock         = $flock;
        $this->breaches      = $breaches;
    }
}
