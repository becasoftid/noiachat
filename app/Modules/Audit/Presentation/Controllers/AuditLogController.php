<?php

namespace App\Modules\Audit\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Domain\Repositories\AuditLogRepositoryInterface;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogRepositoryInterface $auditLogs) {}

    public function index(Request $request)
    {
        $filters = $request->only(['module', 'action', 'user_id', 'target_type', 'date_from', 'date_to']);

        return view('noia.audit.index', [
            'logs' => $this->auditLogs->paginateFiltered($filters),
            'summary' => $this->auditLogs->moduleSummary($filters),
            'users' => $this->auditLogs->usersForFilter(),
        ]);
    }
}
