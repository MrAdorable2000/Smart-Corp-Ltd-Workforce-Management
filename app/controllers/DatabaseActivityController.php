<?php
/**
 * Database Activity Controller
 * Shows ALL database changes (inserts, updates, deletes) with full before/after values
 */
class DatabaseActivityController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('view_audit_logs');

        $page = (int)$this->query('page', 1);
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $filters = [
            'action'    => $this->query('action', ''),
            'module'    => $this->query('module', ''),
            'user_id'   => $this->query('user_id', ''),
            'date_from' => $this->query('date_from', ''),
            'date_to'   => $this->query('date_to', ''),
        ];

        $changes = DatabaseAudit::getRecentChanges($filters, $perPage, $offset);
        $total = DatabaseAudit::getChangesCount($filters);
        $totalPages = ceil($total / $perPage);
        $stats = DatabaseAudit::getTodayStats();
        $modules = DatabaseAudit::getModulesList();
        $users = Database::getInstance()->fetchAll("SELECT id, name, email FROM users ORDER BY name");

        $this->view('database_activity/index', [
            'title' => 'Database Activity',
            'pageTitle' => 'Database Change Tracker',
            'changes' => $changes,
            'stats' => $stats,
            'modules' => $modules,
            'users' => $users,
            'filters' => $filters,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'perPage' => $perPage,
            'csrf' => CSRF::field()
        ]);
    }

    public function view($id)
    {
        $this->requireAuth();
        $this->requirePermission('view_audit_logs');

        $change = DatabaseAudit::getChange($id);
        if (!$change) {
            Flash::set('error', 'Change record not found.');
            $this->redirect('/database-activity');
        }

        // Decode JSON values
        $oldValues = $change['old_values'] ? json_decode($change['old_values'], true) : null;
        $newValues = $change['new_values'] ? json_decode($change['new_values'], true) : null;

        // Calculate field-level diff for updates
        $diff = [];
        if ($oldValues && $newValues) {
            foreach ($newValues as $key => $newVal) {
                $oldVal = $oldValues[$key] ?? null;
                if ($oldVal !== $newVal) {
                    $diff[] = [
                        'field' => $key,
                        'old' => $oldVal,
                        'new' => $newVal,
                    ];
                }
            }
            // Also show fields that were in old but not in new
            foreach ($oldValues as $key => $oldVal) {
                if (!array_key_exists($key, $newValues)) {
                    $diff[] = [
                        'field' => $key,
                        'old' => $oldVal,
                        'new' => '[removed]',
                    ];
                }
            }
        }

        $this->view('database_activity/view', [
            'title' => 'Change Details',
            'pageTitle' => 'Database Change #' . $id,
            'change' => $change,
            'oldValues' => $oldValues,
            'newValues' => $newValues,
            'diff' => $diff,
            'csrf' => CSRF::field()
        ]);
    }

    /**
     * Export changes as CSV
     */
    public function export()
    {
        $this->requireAuth();
        $this->requirePermission('view_audit_logs');

        $changes = DatabaseAudit::getRecentChanges([], 1000, 0);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="database_activity_' . date('Y-m-d_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for Excel UTF-8

        fputcsv($out, ['ID', 'Date', 'User', 'Action', 'Table', 'Description', 'IP', 'Method', 'URL', 'Old Values', 'New Values']);

        foreach ($changes as $c) {
            fputcsv($out, [
                $c['id'],
                $c['created_at'],
                $c['user_name'] ?? 'System',
                $c['action'],
                $c['module'],
                $c['description'],
                $c['ip_address'],
                $c['http_method'],
                $c['request_url'],
                $c['old_values'] ?: '',
                $c['new_values'] ?: '',
            ]);
        }
        fclose($out);
        exit;
    }
}
