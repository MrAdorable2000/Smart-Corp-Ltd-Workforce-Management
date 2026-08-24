<?php
/**
 * Employee Controller
 */
class EmployeeController extends Controller
{
    private $employee;

    public function __construct()
    {
        parent::__construct();
        $this->employee = new Employee();
    }

    public function index()
    {
        $this->requireAuth();
        if (!Auth::can('view_employees') && !Auth::can('manage_employees')) {
            Flash::set('error', 'Access denied.');
            $this->redirect('/dashboard');
        }

        $filters = [
            'search'       => $this->query('search', ''),
            'department_id'=> $this->query('department_id', ''),
            'status'       => $this->query('status', ''),
            'active'       => 1
        ];

        $employees = $this->employee->getAllWithRelations($filters);
        $departments = Database::getInstance()->fetchAll("SELECT * FROM departments WHERE is_active = 1");

        $this->view('employees/index', [
            'title' => 'Employees',
            'pageTitle' => 'Employee Management',
            'employees' => $employees,
            'departments' => $departments,
            'filters' => $filters,
            'csrf' => CSRF::field(),
            'stats' => $this->employee->getStats()
        ]);
    }

    public function create()
    {
        $this->requireAuth();
        $this->requirePermission('manage_employees');

        $departments = Database::getInstance()->fetchAll("SELECT * FROM departments WHERE is_active = 1");
        $branches    = Database::getInstance()->fetchAll("SELECT * FROM branches WHERE is_active = 1");
        $shifts      = Database::getInstance()->fetchAll("SELECT * FROM shifts WHERE is_active = 1");
        $newCode     = $this->employee->generateEmployeeCode();

        $this->view('employees/create', [
            'title' => 'Add Employee',
            'pageTitle' => 'Add New Employee',
            'departments' => $departments,
            'branches' => $branches,
            'shifts' => $shifts,
            'newCode' => $newCode,
            'csrf' => CSRF::field()
        ]);
    }

    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_employees');

        $data = $this->input();
        $validator = new Validator($data);
        $validator->required('first_name', 'First name')
                  ->required('last_name', 'Last name')
                  ->required('employee_code', 'Employee code')
                  ->required('date_joined', 'Date joined')
                  ->email('email')
                  ->unique('employee_code', 'employees', null, null, 'Employee code')
                  ->unique('email', 'employees', null, null, 'Email');

        if ($validator->fails()) {
            Flash::set('error', $validator->firstError());
            $this->redirect('/employees/create');
        }

        // Handle photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploader = new Uploader('photo', ALLOWED_IMAGE_TYPES);
                $data['photo'] = $uploader->upload('employees', 'emp_' . time());
            } catch (Exception $e) {
                Flash::set('error', 'Photo upload failed: ' . $e->getMessage());
                $this->redirect('/employees/create');
            }
        }

        $data['company_id'] = 1; // Default company
        $data['created_by'] = Auth::id();
        $data['is_active'] = 1;

        $id = $this->employee->create($data);
        Auth::audit('create', 'employees', "Created employee {$data['first_name']} {$data['last_name']} ({$data['employee_code']})", null, $data);

        Flash::set('success', 'Employee added successfully.');
        $this->redirect('/employees/' . $id);
    }

    public function show($id)
    {
        $this->requireAuth();
        if (!Auth::can('view_employees') && !Auth::can('manage_employees')) {
            Flash::set('error', 'Access denied.');
            $this->redirect('/dashboard');
        }

        $employee = $this->employee->getWithRelations($id);
        if (!$employee) {
            Flash::set('error', 'Employee not found.');
            $this->redirect('/employees');
        }

        $documents = $this->employee->getDocuments($id);
        $faceData = $this->employee->getFaceData($id);
        $attendanceHistory = $this->employee->getAttendanceHistory($id);
        $leaveHistory = $this->employee->getLeaveHistory($id);

        $this->view('employees/view', [
            'title' => 'Employee Profile',
            'pageTitle' => $employee['first_name'] . ' ' . $employee['last_name'],
            'employee' => $employee,
            'documents' => $documents,
            'faceData' => $faceData,
            'attendanceHistory' => $attendanceHistory,
            'leaveHistory' => $leaveHistory,
            'csrf' => CSRF::field()
        ]);
    }

    public function edit($id)
    {
        $this->requireAuth();
        $this->requirePermission('manage_employees');

        $employee = $this->employee->find($id);
        if (!$employee) {
            Flash::set('error', 'Employee not found.');
            $this->redirect('/employees');
        }

        $departments = Database::getInstance()->fetchAll("SELECT * FROM departments WHERE is_active = 1");
        $branches    = Database::getInstance()->fetchAll("SELECT * FROM branches WHERE is_active = 1");
        $shifts      = Database::getInstance()->fetchAll("SELECT * FROM shifts WHERE is_active = 1");

        $this->view('employees/edit', [
            'title' => 'Edit Employee',
            'pageTitle' => 'Edit ' . $employee['first_name'] . ' ' . $employee['last_name'],
            'employee' => $employee,
            'departments' => $departments,
            'branches' => $branches,
            'shifts' => $shifts,
            'csrf' => CSRF::field()
        ]);
    }

    public function update($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_employees');

        $data = $this->input();
        $validator = new Validator($data);
        $validator->required('first_name')->required('last_name')->required('date_joined')->email('email');

        if ($validator->fails()) {
            Flash::set('error', $validator->firstError());
            $this->redirect('/employees/' . $id . '/edit');
        }

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploader = new Uploader('photo', ALLOWED_IMAGE_TYPES);
                $data['photo'] = $uploader->upload('employees', 'emp_' . $id);
            } catch (Exception $e) {
                Flash::set('error', 'Photo upload failed: ' . $e->getMessage());
                $this->redirect('/employees/' . $id . '/edit');
            }
        }

        // Remove fields not in fillable
        unset($data[CSRF_TOKEN_NAME], $data['_method'], $data['id']);

        $this->employee->update($id, $data);
        Auth::audit('update', 'employees', "Updated employee ID {$id}", null, $data);

        Flash::set('success', 'Employee updated successfully.');
        $this->redirect('/employees/' . $id);
    }

    public function destroy($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_employees');

        $employee = $this->employee->find($id);
        if (!$employee) {
            $this->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        // Soft delete
        $this->employee->update($id, ['is_active' => 0, 'date_left' => date('Y-m-d')]);
        Auth::audit('delete', 'employees', "Deactivated employee {$employee['employee_code']}", $employee);

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'Employee deactivated']);
        }
        Flash::set('success', 'Employee deactivated successfully.');
        $this->redirect('/employees');
    }

    /**
     * AJAX face enrollment trigger (view redirect)
     */
    public function faceEnroll($id)
    {
        $this->requireAuth();
        $this->requirePermission('manage_face_data');
        $employee = $this->employee->find($id);
        if (!$employee) {
            Flash::set('error', 'Employee not found.');
            $this->redirect('/employees');
        }
        $this->redirect('/face/enroll/' . $id);
    }

    /**
     * API: list employees as JSON
     */
    public function apiList()
    {
        $this->requireAuth();
        $filters = [
            'search' => $this->query('q', ''),
            'active' => 1
        ];
        $employees = $this->employee->getAllWithRelations($filters);
        $this->json(['success' => true, 'data' => $employees]);
    }

    public function apiShow($id)
    {
        $this->requireAuth();
        $employee = $this->employee->getWithRelations($id);
        if (!$employee) $this->json(['success' => false, 'message' => 'Not found'], 404);
        $this->json(['success' => true, 'data' => $employee]);
    }
}
