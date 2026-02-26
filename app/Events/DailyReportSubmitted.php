<?php

namespace App\Events;

use App\Models\Flock;
use App\Models\ProductionLog;
use App\Models\Shed;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DailyReportSubmitted
{
    use Dispatchable, SerializesModels;

    public ProductionLog $productionLog;
    public Shed $shed;
    public Flock $flock;

    public function __construct(ProductionLog $productionLog, Shed $shed, Flock $flock)
    {
        $this->productionLog = $productionLog;
        $this->shed          = $shed;
        $this->flock         = $flock;
    }
}
