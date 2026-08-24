<?php
/**
 * Employee Model
 */
class Employee extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'id';
    protected $fillable = [
        'employee_code', 'company_id', 'branch_id', 'department_id', 'shift_id',
        'first_name', 'last_name', 'gender', 'date_of_birth', 'national_id',
        'passport_number', 'phone', 'email', 'address', 'city', 'country',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
        'position', 'job_title', 'employment_status', 'employment_type',
        'salary', 'allowance', 'tax_rate', 'date_joined', 'date_left',
        'photo', 'gps_attendance_required', 'face_enrolled', 'is_active', 'created_by'
    ];

    /**
     * Get employees with related data
     */
    public function getAllWithRelations($filters = [])
    {
        $sql = "SELECT e.*, d.name AS department_name, d.code AS department_code,
                       b.name AS branch_name, s.name AS shift_name,
                       CONCAT(e.first_name, ' ', e.last_name) AS full_name
                FROM employees e
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN branches b ON b.id = e.branch_id
                LEFT JOIN shifts s ON s.id = e.shift_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['department_id'])) {
            $sql .= " AND e.department_id = :dept";
            $params['dept'] = $filters['department_id'];
        }
        if (!empty($filters['branch_id'])) {
            $sql .= " AND e.branch_id = :br";
            $params['br'] = $filters['branch_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND e.employment_status = :st";
            $params['st'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (e.first_name LIKE :q OR e.last_name LIKE :q OR e.employee_code LIKE :q OR e.email LIKE :q)";
            $params['q'] = '%' . $filters['search'] . '%';
        }
        if (isset($filters['active'])) {
            $sql .= " AND e.is_active = :a";
            $params['a'] = $filters['active'];
        }

        $sql .= " ORDER BY e.created_at DESC";
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return Database::getInstance()->fetchAll($sql, $params);
    }

    public function getWithRelations($id)
    {
        return Database::getInstance()->fetch(
            "SELECT e.*, d.name AS department_name, b.name AS branch_name,
                    s.name AS shift_name, s.start_time, s.end_time,
                    CONCAT(e.first_name, ' ', e.last_name) AS full_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN branches b ON b.id = e.branch_id
             LEFT JOIN shifts s ON s.id = e.shift_id
             WHERE e.id = :id",
            ['id' => $id]
        );
    }

    public function generateEmployeeCode()
    {
        $last = Database::getInstance()->fetch(
            "SELECT employee_code FROM employees ORDER BY id DESC LIMIT 1"
        );
        if ($last && preg_match('/(\d+)$/', $last['employee_code'], $m)) {
            return 'EMP' . str_pad((int)$m[1] + 1, 3, '0', STR_PAD_LEFT);
        }
        return 'EMP001';
    }

    public function getDocuments($employeeId)
    {
        return Database::getInstance()->fetchAll(
            "SELECT * FROM employee_documents WHERE employee_id = :id ORDER BY created_at DESC",
            ['id' => $employeeId]
        );
    }

    public function getFaceData($employeeId)
    {
        return Database::getInstance()->fetchAll(
            "SELECT * FROM employee_faces WHERE employee_id = :id AND is_active = 1",
            ['id' => $employeeId]
        );
    }

    public function getAttendanceHistory($employeeId, $limit = 30)
    {
        return Database::getInstance()->fetchAll(
            "SELECT * FROM attendance WHERE employee_id = :id ORDER BY attendance_date DESC LIMIT {$limit}",
            ['id' => $employeeId]
        );
    }

    public function getLeaveHistory($employeeId)
    {
        return Database::getInstance()->fetchAll(
            "SELECT lr.*, lt.name AS leave_type
             FROM leave_requests lr
             INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
             WHERE lr.employee_id = :id
             ORDER BY lr.created_at DESC",
            ['id' => $employeeId]
        );
    }

    public function getStats()
    {
        $db = Database::getInstance();
        return [
            'total'       => $db->count('employees', 'is_active = 1'),
            'permanent'   => $db->count('employees', "employment_status = 'permanent' AND is_active = 1"),
            'contract'    => $db->count('employees', "employment_status = 'contract' AND is_active = 1"),
            'probation'   => $db->count('employees', "employment_status = 'probation' AND is_active = 1"),
            'face_enrolled' => $db->count('employees', 'face_enrolled = 1 AND is_active = 1'),
            'by_department' => $db->fetchAll(
                "SELECT d.name, COUNT(e.id) as count FROM departments d
                 LEFT JOIN employees e ON e.department_id = d.id AND e.is_active = 1
                 WHERE d.is_active = 1 GROUP BY d.id"
            )
        ];
    }
}
