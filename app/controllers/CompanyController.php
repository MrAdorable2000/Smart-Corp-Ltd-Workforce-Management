<?php
class CompanyController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('manage_company');
        $company = Database::getInstance()->fetch("SELECT * FROM companies WHERE id = 1");
        $this->view('company/index', [
            'title' => 'Company',
            'pageTitle' => 'Company Profile',
            'company' => $company,
            'csrf' => CSRF::field()
        ]);
    }

    public function update()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_company');

        $data = $this->input();
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploader = new Uploader('logo', ALLOWED_IMAGE_TYPES);
                $data['logo'] = $uploader->upload('company', 'logo');
            } catch (Exception $e) {
                Flash::set('error', 'Logo upload failed');
                $this->redirect('/company');
            }
        }
        unset($data[CSRF_TOKEN_NAME], $data['_method']);
        Database::getInstance()->update('companies', $data, 'id = :id', ['id' => 1]);
        Auth::audit('update', 'company', 'Updated company profile');
        Flash::set('success', 'Company profile updated.');
        $this->redirect('/company');
    }
}
