<?php
class BranchController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('manage_company');
        $branches = Database::getInstance()->fetchAll(
            "SELECT b.*, c.name AS company_name,
                    (SELECT COUNT(*) FROM employees e WHERE e.branch_id = b.id AND e.is_active = 1) AS employee_count
             FROM branches b
             INNER JOIN companies c ON c.id = b.company_id
             ORDER BY b.name"
        );
        $this->view('branches/index', [
            'title' => 'Branches',
            'pageTitle' => 'Branch Management',
            'branches' => $branches,
            'csrf' => CSRF::field()
        ]);
    }

    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_company');
        $data = $this->input();
        Database::getInstance()->insert('branches', [
            'company_id' => 1,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'latitude' => $data['latitude'] ?: null,
            'longitude' => $data['longitude'] ?: null,
            'geofence_radius' => $data['geofence_radius'] ?? 100,
            'is_active' => 1
        ]);
        Auth::audit('create', 'branches', "Created branch {$data['name']}");
        Flash::set('success', 'Branch added.');
        $this->redirect('/branches');
    }

    public function update($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_company');
        $data = $this->input();
        // Strip non-data fields to prevent accidental column overwrites
        unset($data[CSRF_TOKEN_NAME], $data['_method'], $data['id']);
        Database::getInstance()->update('branches', $data, 'id = :id', ['id' => $id]);
        Auth::audit('update', 'branches', "Updated branch ID {$id}");
        Flash::set('success', 'Branch updated.');
        $this->redirect('/branches');
    }

    public function destroy($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_company');

        $branch = Database::getInstance()->fetch("SELECT * FROM branches WHERE id = :id", ['id' => $id]);
        if (!$branch) { Flash::set('error', 'Not found'); $this->redirect('/branches'); }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            // Unlink employees from this branch
            $db->update('employees', ['branch_id' => null], 'branch_id = :bid', ['bid' => $id]);
            // Unlink departments from this branch
            $db->update('departments', ['branch_id' => null], 'branch_id = :bid', ['bid' => $id]);
            // PERMANENTLY DELETE the branch
            $db->delete('branches', 'id = :id', ['id' => $id]);
            $db->commit();
            Auth::audit('delete', 'branches', "PERMANENTLY DELETED branch: {$branch['name']}");
            Flash::set('success', "Branch '{$branch['name']}' permanently deleted.");
        } catch (Exception $e) {
            $db->rollBack();
            Flash::set('error', 'Failed: ' . $e->getMessage());
        }
        $this->redirect('/branches');
    }
}
