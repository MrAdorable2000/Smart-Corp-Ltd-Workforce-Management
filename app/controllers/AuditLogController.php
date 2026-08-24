<?php
class AuditLogController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('view_audit_logs');

        $page = (int) $this->query('page', 1);
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $module = $this->query('module', '');
        $action = $this->query('action', '');

        $where = '1=1';
        $params = [];
        if ($module) { $where .= " AND module = :m"; $params['m'] = $module; }
        if ($action) { $where .= " AND action = :a"; $params['a'] = $action; }

        $total = Database::getInstance()->count('audit_logs', $where, $params);
        $totalPages = ceil($total / $perPage);

        $sql = "SELECT al.*, u.name AS user_name, u.email AS user_email
                FROM audit_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE {$where}
                ORDER BY al.created_at DESC
                LIMIT {$offset}, {$perPage}";

        $logs = Database::getInstance()->fetchAll($sql, $params);

        $modules = Database::getInstance()->fetchAll("SELECT DISTINCT module FROM audit_logs ORDER BY module");
        $actions = Database::getInstance()->fetchAll("SELECT DISTINCT action FROM audit_logs ORDER BY action");

        $this->view('audit_logs/index', [
            'title' => 'Audit Logs',
            'pageTitle' => 'System Audit Logs',
            'logs' => $logs,
            'modules' => $modules,
            'actions' => $actions,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'currentModule' => $module,
            'currentAction' => $action
        ]);
    }
}
