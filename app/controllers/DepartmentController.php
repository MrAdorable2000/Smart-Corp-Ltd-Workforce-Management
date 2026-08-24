<?php
class DepartmentController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('manage_departments');

        $departments = Database::getInstance()->fetchAll(
            "SELECT d.*, b.name AS branch_name,
                    (SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id AND e.is_active = 1) AS employee_count
             FROM departments d
             LEFT JOIN branches b ON b.id = d.branch_id
             WHERE d.is_active = 1
             ORDER BY d.name"
        );

        $branches = Database::getInstance()->fetchAll("SELECT * FROM branches WHERE is_active = 1");

        $this->view('departments/index', [
            'title' => 'Departments',
            'pageTitle' => 'Department Management',
            'departments' => $departments,
            'branches' => $branches,
            'csrf' => CSRF::field()
        ]);
    }

    public function create()
    {
        $this->requireAuth();
        $this->requirePermission('manage_departments');
        $branches = Database::getInstance()->fetchAll("SELECT * FROM branches WHERE is_active = 1");
        $this->view('departments/form', [
            'title' => 'Add Department',
            'pageTitle' => 'Add New Department',
            'department' => null,
            'branches' => $branches,
            'csrf' => CSRF::field()
        ]);
    }

    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_departments');

        $data = $this->input();
        $validator = new Validator($data);
        $validator->required('name', 'Department name')->required('code', 'Code')
                  ->unique('code', 'departments', null, null, 'Code');

        if ($validator->fails()) {
            Flash::set('error', $validator->firstError());
            $this->redirect('/departments/create');
        }

        $id = Database::getInstance()->insert('departments', [
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'description' => $data['description'] ?? null,
            'branch_id' => $data['branch_id'] ?: null,
            'is_active' => 1
        ]);
        Auth::audit('create', 'departments', "Created department {$data['name']}");
        Flash::set('success', 'Department created successfully.');
        $this->redirect('/departments');
    }

    public function edit($id)
    {
        $this->requireAuth();
        $this->requirePermission('manage_departments');
        $department = Database::getInstance()->fetch("SELECT * FROM departments WHERE id = :id", ['id' => $id]);
        if (!$department) { Flash::set('error', 'Not found'); $this->redirect('/departments'); }
        $branches = Database::getInstance()->fetchAll("SELECT * FROM branches WHERE is_active = 1");
        $this->view('departments/form', [
            'title' => 'Edit Department',
            'pageTitle' => 'Edit ' . $department['name'],
            'department' => $department,
            'branches' => $branches,
            'csrf' => CSRF::field()
        ]);
    }

    public function update($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_departments');

        $data = $this->input();
        Database::getInstance()->update('departments', [
            'name' => $data['name'],
            'code' => strtoupper($data['code']),
            'description' => $data['description'] ?? null,
            'branch_id' => $data['branch_id'] ?: null
        ], 'id = :id', ['id' => $id]);

        Auth::audit('update', 'departments', "Updated department ID {$id}");
        Flash::set('success', 'Department updated.');
        $this->redirect('/departments');
    }

    public function destroy($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_departments');

        $dept = Database::getInstance()->fetch("SELECT * FROM departments WHERE id = :id", ['id' => $id]);
        if (!$dept) { Flash::set('error', 'Not found'); $this->redirect('/departments'); }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            // Unlink employees from this department
            $db->update('employees', ['department_id' => null], 'department_id = :did', ['did' => $id]);
            // PERMANENTLY DELETE the department
            $db->delete('departments', 'id = :id', ['id' => $id]);
            $db->commit();
            Auth::audit('delete', 'departments', "PERMANENTLY DELETED department: {$dept['name']}");
            if ($this->isAjax()) $this->json(['success' => true]);
            Flash::set('success', "Department '{$dept['name']}' permanently deleted.");
        } catch (Exception $e) {
            $db->rollBack();
            Flash::set('error', 'Failed: ' . $e->getMessage());
        }
        $this->redirect('/departments');
    }

    public function apiList()
    {
        $this->requireAuth();
        $list = Database::getInstance()->fetchAll("SELECT * FROM departments WHERE is_active = 1 ORDER BY name");
        $this->json(['success' => true, 'data' => $list]);
    }
}
