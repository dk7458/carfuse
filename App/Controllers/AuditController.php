<?php

namespace App\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Services\AuditService;
/**
 * AuditController - Handles viewing and retrieving audit logs.
 */
class AuditController extends Controller
{
    /**
     * ✅ Render the audit log view for the admin.
     */
    public function index(Request $request)
    {
        try {
            // Validate request filters (add rules as needed)
            $filters = $request->validate([
                // 'field_name' => 'rule'
            ]);
            $logs = AuditLog::where($filters)->latest()->paginate(10);
            // Pass $logs to the included view
            extract(compact('logs'));
            include 'public/views/admin/audit_logs.php';
        } catch (\Exception $e) {
            // Centralized error handling
            return $this->handleException($e);
        }
    }

    /**
     * ✅ API Endpoint: Fetch logs based on filters.
     */
    public function fetchLogs(Request $request)
    {
        try {
            // Validate request filters (add rules as needed)
            $filters = $request->validate([
                // 'field_name' => 'rule'
            ]);
            $logs = AuditLog::where($filters)->latest()->paginate(10);
            return $this->jsonResponse(['status' => 'success', 'logs' => $logs]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
