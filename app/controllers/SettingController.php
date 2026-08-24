<?php
class SettingController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('manage_settings');
        $settings = Database::getInstance()->fetchAll("SELECT * FROM settings ORDER BY setting_group, setting_key");
        $grouped = [];
        foreach ($settings as $s) {
            $grouped[$s['setting_group']][] = $s;
        }

        // Load company info (system name, logo, etc.)
        $company = Database::getInstance()->fetch("SELECT * FROM companies WHERE id = 1");

        $this->view('settings/index', [
            'title' => 'Settings',
            'pageTitle' => 'System Settings',
            'grouped' => $grouped,
            'company' => $company,
            'csrf' => CSRF::field()
        ]);
    }

    public function update()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_settings');

        $data = $this->input();
        unset($data[CSRF_TOKEN_NAME], $data['_method']);

        // Handle company info (system name) separately
        // These are the ONLY fields from the company form
        $companyFields = ['name', 'legal_name', 'tax_id', 'email', 'phone', 'address', 'city', 'state', 'country', 'postal_code', 'currency', 'timezone', 'website'];
        $companyData = [];
        foreach ($companyFields as $f) {
            if (isset($data[$f])) {
                $companyData[$f] = $data[$f];
                unset($data[$f]);
            }
        }

        // Update company if any company fields were submitted
        if (!empty($companyData)) {
            // Handle logo upload
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                try {
                    $uploader = new Uploader('logo', ALLOWED_IMAGE_TYPES);
                    $companyData['logo'] = $uploader->upload('company', 'logo');
                } catch (Exception $e) {}
            }
            // Preserve is_active = 1 (don't accidentally deactivate the company)
            $companyData['is_active'] = 1;
            Database::getInstance()->update('companies', $companyData, 'id = :id', ['id' => 1]);
            Auth::audit('update', 'company', 'Updated company/system information: ' . ($companyData['name'] ?? ''));
        }

        // Update other settings (only if there are remaining fields)
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $existing = Database::getInstance()->fetch(
                    "SELECT id FROM settings WHERE setting_key = :k",
                    ['k' => $key]
                );
                if ($existing) {
                    Database::getInstance()->update('settings',
                        ['setting_value' => $value],
                        'setting_key = :k', ['k' => $key]);
                } else {
                    Database::getInstance()->insert('settings', [
                        'setting_key' => $key,
                        'setting_value' => $value,
                        'setting_group' => 'general'
                    ]);
                }
            }
            Auth::audit('update', 'settings', "Updated system settings");
        }

        Flash::set('success', 'Settings updated successfully.');
        $this->redirect('/settings');
    }
}
