<?php

namespace App\Core\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Admin']) && !$user->can('view audit logs'))) {
            abort(403, 'Unauthorized to view audit logs.');
        }

        $logs = DB::table('audit_logs')
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
            ->select('audit_logs.*', 'users.name as user_name', 'users.email as user_email')
            ->orderBy('audit_logs.created_at', 'desc')
            ->limit(100)
            ->get();
            
        // Decode JSON values for the frontend and redact sensitive keys
        $logs->transform(function ($log) {
            if (is_string($log->old_values)) {
                $log->old_values = json_decode($log->old_values, true);
            }
            if (is_string($log->new_values)) {
                $log->new_values = json_decode($log->new_values, true);
            }

            $log->old_values = \App\Core\Support\AuditSensitiveDataRedactor::redact($log->old_values);
            $log->new_values = \App\Core\Support\AuditSensitiveDataRedactor::redact($log->new_values);

            return $log;
        });

        return response()->json(['data' => $logs]);
    }
}
