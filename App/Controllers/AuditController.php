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
    public function index()
    {
        try {
            // Replace request->validate(...) with native PHP filters (assuming $_POST).
            $filters = $_POST; // custom validation can be performed as needed
            $logs = AuditLog::where($filters)->latest()->paginate(10);
            extract(compact('logs'));
            include BASE_PATH . '/public/views/admin/audit_logs.php';
        } catch (\Exception $e) {
            // Centralized error handling
            return $this->handleException($e);
        }
    }

    /**
     * ✅ API Endpoint: Fetch logs based on filters.
     */
    public function fetchLogs()
    {
        try {
            $filters = $_POST; // custom validation as needed
            $logs = AuditLog::where($filters)->latest()->paginate(10);
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode(['status' => 'success', 'logs' => $logs]);
            exit;
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
