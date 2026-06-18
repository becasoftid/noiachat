<?php

namespace App\Modules\Reports\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Application\Services\HealthMonitorService;

class HealthController extends Controller
{
    public function __invoke(HealthMonitorService $health)
    {
        return view('noia.health.index', $health->summary());
    }
}
