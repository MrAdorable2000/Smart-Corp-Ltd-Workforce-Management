<?php
class ShiftController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('manage_shifts');
        $shifts = Database::getInstance()->fetchAll("SELECT * FROM shifts ORDER BY start_time");
        $this->view('shifts/index', [
            'title' => 'Shifts', 'pageTitle' => 'Shift Management',
            'shifts' => $shifts, 'csrf' => CSRF::field()
        ]);
    }

    public function create()
    {
        $this->requireAuth();
        $this->requirePermission('manage_shifts');
        $this->view('shifts/form', [
            'title' => 'Add Shift', 'pageTitle' => 'Add New Shift',
            'shift' => null, 'csrf' => CSRF::field()
        ]);
    }

    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_shifts');
        $data = $this->input();
        $validator = new Validator($data);
        $validator->required('name')->required('start_time')->required('end_time');
        if ($validator->fails()) { Flash::set('error', $validator->firstError()); $this->redirect('/shifts/create'); }

        Database::getInstance()->insert('shifts', [
            'name' => $data['name'], 'code' => $data['code'] ?? null,
            'start_time' => $data['start_time'], 'end_time' => $data['end_time'],
            'grace_period_minutes' => $data['grace_period_minutes'] ?? 15,
            'late_threshold_minutes' => $data['late_threshold_minutes'] ?? 15,
            'early_leave_threshold_minutes' => $data['early_leave_threshold_minutes'] ?? 15,
            'overtime_eligible' => $data['overtime_eligible'] ?? 0,
            'overtime_rate' => $data['overtime_rate'] ?? 1.50,
            'is_night_shift' => $data['is_night_shift'] ?? 0,
            'is_flexible' => $data['is_flexible'] ?? 0,
            'working_hours_per_day' => $data['working_hours_per_day'] ?? 8.00,
            'description' => $data['description'] ?? null,
            'is_active' => 1
        ]);
        Auth::audit('create', 'shifts', "Created shift {$data['name']}");
        Flash::set('success', 'Shift created.');
        $this->redirect('/shifts');
    }

    public function edit($id)
    {
        $this->requireAuth();
        $this->requirePermission('manage_shifts');
        $shift = Database::getInstance()->fetch("SELECT * FROM shifts WHERE id = :id", ['id' => $id]);
        if (!$shift) { Flash::set('error', 'Not found'); $this->redirect('/shifts'); }
        $this->view('shifts/form', [
            'title' => 'Edit Shift', 'pageTitle' => 'Edit ' . $shift['name'],
            'shift' => $shift, 'csrf' => CSRF::field()
        ]);
    }

    public function update($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_shifts');
        $data = $this->input();
        Database::getInstance()->update('shifts', [
            'name' => $data['name'], 'code' => $data['code'] ?? null,
            'start_time' => $data['start_time'], 'end_time' => $data['end_time'],
            'grace_period_minutes' => $data['grace_period_minutes'] ?? 15,
            'late_threshold_minutes' => $data['late_threshold_minutes'] ?? 15,
            'early_leave_threshold_minutes' => $data['early_leave_threshold_minutes'] ?? 15,
            'overtime_eligible' => $data['overtime_eligible'] ?? 0,
            'overtime_rate' => $data['overtime_rate'] ?? 1.50,
            'is_night_shift' => $data['is_night_shift'] ?? 0,
            'is_flexible' => $data['is_flexible'] ?? 0,
            'working_hours_per_day' => $data['working_hours_per_day'] ?? 8.00,
            'description' => $data['description'] ?? null
        ], 'id = :id', ['id' => $id]);
        Auth::audit('update', 'shifts', "Updated shift ID {$id}");
        Flash::set('success', 'Shift updated.');
        $this->redirect('/shifts');
    }

    public function destroy($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_shifts');

        $shift = Database::getInstance()->fetch("SELECT * FROM shifts WHERE id = :id", ['id' => $id]);
        if (!$shift) {
            Flash::set('error', 'Shift not found.');
            $this->redirect('/shifts');
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            // Set shift_id to NULL in employees that reference this shift
            $db->update('employees', ['shift_id' => null], 'shift_id = :sid', ['sid' => $id]);
            // Set shift_id to NULL in attendance records
            $db->update('attendance', ['shift_id' => null], 'shift_id = :sid', ['sid' => $id]);
            // PERMANENTLY DELETE the shift record
            $db->delete('shifts', 'id = :id', ['id' => $id]);
            $db->commit();

            Auth::audit('delete', 'shifts', "PERMANENTLY DELETED shift: {$shift['name']} (ID: {$id})");
            if ($this->isAjax()) $this->json(['success' => true]);
            Flash::set('success', "Shift '{$shift['name']}' has been permanently deleted from the database.");
        } catch (Exception $e) {
            $db->rollBack();
            Flash::set('error', 'Failed to delete shift: ' . $e->getMessage());
        }
        $this->redirect('/shifts');
    }
}
