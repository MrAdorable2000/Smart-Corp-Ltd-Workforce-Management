<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Add New Employee</h4>
        <p class="text-muted mb-0">Create a new employee record with full profile</p>
    </div>
    <a href="<?= BASE_URL ?>/employees" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/employees/store" enctype="multipart/form-data">
    <?= $csrf ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <!-- Personal Information -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-person me-2"></i>Personal Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">First Name</label>
                            <input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="<?= $_POST['date_of_birth'] ?? '' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">National ID</label>
                            <input type="text" name="national_id" class="form-control" value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Passport Number</label>
                            <input type="text" name="passport_number" class="form-control" value="<?= htmlspecialchars($_POST['passport_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($_POST['country'] ?? 'United States') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-telephone me-2"></i>Emergency Contact</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Contact Name</label>
                            <input type="text" name="emergency_contact_name" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="emergency_contact_phone" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Relationship</label>
                            <input type="text" name="emergency_contact_relation" class="form-control" placeholder="e.g. Spouse">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employment Details -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-briefcase me-2"></i>Employment Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label required">Employee Code</label>
                            <input type="text" name="employee_code" class="form-control" required value="<?= htmlspecialchars($_POST['employee_code'] ?? $newCode) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">-- Select --</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= ($_POST['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">-- Select --</option>
                                <?php foreach ($branches as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= ($_POST['branch_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($_POST['position'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Job Title</label>
                            <input type="text" name="job_title" class="form-control" value="<?= htmlspecialchars($_POST['job_title'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Shift</label>
                            <select name="shift_id" class="form-select">
                                <option value="">-- Select --</option>
                                <?php foreach ($shifts as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($_POST['shift_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?> (<?= $s['start_time'] ?>-<?= $s['end_time'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Employment Status</label>
                            <select name="employment_status" class="form-select">
                                <option value="permanent">Permanent</option>
                                <option value="contract">Contract</option>
                                <option value="probation">Probation</option>
                                <option value="intern">Intern</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Employment Type</label>
                            <select name="employment_type" class="form-select">
                                <option value="full_time">Full Time</option>
                                <option value="part_time">Part Time</option>
                                <option value="remote">Remote</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Date Joined</label>
                            <input type="date" name="date_joined" class="form-control" required value="<?= $_POST['date_joined'] ?? date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compensation -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-cash-coin me-2"></i>Compensation</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Basic Salary (USD)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="salary" class="form-control" value="<?= $_POST['salary'] ?? '0.00' ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Allowance (USD)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="allowance" class="form-control" value="<?= $_POST['allowance'] ?? '0.00' ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tax Rate (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="tax_rate" class="form-control" value="<?= $_POST['tax_rate'] ?? '0.00' ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-image me-2"></i>Employee Photo</div>
                <div class="card-body text-center">
                    <div id="photoPreview" class="photo-preview mb-3">
                        <i class="bi bi-person-circle display-1 text-muted"></i>
                        <p class="text-muted-sm mt-2">No photo selected</p>
                    </div>
                    <input type="file" name="photo" id="photoInput" class="form-control" accept="image/*">
                    <small class="text-muted">JPG, PNG, max 5MB</small>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-geo-alt me-2"></i>GPS Attendance</div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="gps_attendance_required" class="form-check-input" id="gpsReq" value="1">
                        <label class="form-check-label" for="gpsReq">Require GPS verification for check-in</label>
                    </div>
                    <p class="text-muted-sm mt-2">When enabled, employee must be within office geofence radius to mark attendance.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-check-lg"></i> Save Employee
                    </button>
                    <a href="<?= BASE_URL ?>/employees" class="btn btn-light w-100">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.photo-preview {
    width: 180px; height: 180px;
    border-radius: 50%;
    border: 2px dashed var(--border);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    background: var(--light);
    overflow: hidden;
}
.photo-preview img { width: 100%; height: 100%; object-fit: cover; }
</style>

<script>
$('#photoInput').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            $('#photoPreview').html('<img src="' + ev.target.result + '" alt="Preview">');
        };
        reader.readAsDataURL(file);
    }
});
</script>
