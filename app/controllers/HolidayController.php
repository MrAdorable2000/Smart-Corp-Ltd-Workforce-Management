<?php
class HolidayController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('manage_holidays');
        $holidays = Database::getInstance()->fetchAll("SELECT * FROM holidays ORDER BY holiday_date");
        $this->view('holidays/index', [
            'title' => 'Holidays',
            'pageTitle' => 'Holiday Configuration',
            'holidays' => $holidays,
            'csrf' => CSRF::field()
        ]);
    }

    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_holidays');
        $data = $this->input();
        Database::getInstance()->insert('holidays', [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'holiday_date' => $data['holiday_date'],
            'end_date' => $data['end_date'] ?? null,
            'is_recurring' => $data['is_recurring'] ?? 0,
            'type' => $data['type'] ?? 'public',
            'country' => $data['country'] ?? null,
            'is_active' => 1
        ]);
        Auth::audit('create', 'holidays', "Added holiday {$data['name']}");
        Flash::set('success', 'Holiday added.');
        $this->redirect('/holidays');
    }

    public function update($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_holidays');
        $data = $this->input();
        unset($data[CSRF_TOKEN_NAME], $data['_method'], $data['id']);
        Database::getInstance()->update('holidays', $data, 'id = :id', ['id' => $id]);
        Auth::audit('update', 'holidays', "Updated holiday ID {$id}");
        Flash::set('success', 'Holiday updated.');
        $this->redirect('/holidays');
    }

    public function destroy($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_holidays');

        $holiday = Database::getInstance()->fetch("SELECT * FROM holidays WHERE id = :id", ['id' => $id]);
        if (!$holiday) { Flash::set('error', 'Not found'); $this->redirect('/holidays'); }

        Database::getInstance()->delete('holidays', 'id = :id', ['id' => $id]);
        Auth::audit('delete', 'holidays', "PERMANENTLY DELETED holiday: {$holiday['name']}");
        Flash::set('success', "Holiday '{$holiday['name']}' permanently deleted.");
        $this->redirect('/holidays');
    }
}
