<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="employee-avatar" style="width:64px;height:64px;font-size:20px;">
            <?php if (!empty($employee['photo']) && file_exists(UPLOAD_PATH . '/' . $employee['photo'])): ?>
                <img src="<?= UPLOAD_URL . '/' . $employee['photo'] ?>" alt="">
            <?php else: ?>
                <?= strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div>
            <h4 class="mb-0"><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></h4>
            <p class="text-muted mb-0"><?= htmlspecialchars($employee['position'] ?: $employee['job_title'] ?: 'Employee') ?> · <?= htmlspecialchars($employee['employee_code']) ?></p>
        </div>
    </div>
    <div>
        <?php if (Auth::can('manage_face_data')): ?>
        <a href="<?= BASE_URL ?>/face/enroll/<?= $employee['id'] ?>" class="btn btn-light">
            <i class="bi bi-camera-video"></i> Enroll Face
        </a>
        <?php endif; ?>
        <?php if (Auth::can('manage_employees')): ?>
        <a href="<?= BASE_URL ?>/employees/<?= $employee['id'] ?>/edit" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <!-- Profile Card -->
        <div class="card mb-3">
            <div class="card-body text-center">
                <div class="employee-avatar mb-3" style="width:120px;height:120px;font-size:36px;margin:0 auto;">
                    <?php if (!empty($employee['photo']) && file_exists(UPLOAD_PATH . '/' . $employee['photo'])): ?>
                        <img src="<?= UPLOAD_URL . '/' . $employee['photo'] ?>" alt="">
                    <?php else: ?>
                        <?= strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <h5 class="mb-1"><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></h5>
                <p class="text-muted mb-2"><?= htmlspecialchars($employee['job_title'] ?: $employee['position'] ?: '-') ?></p>
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-primary"><?= htmlspecialchars($employee['employee_code']) ?></span>
                    <span class="badge bg-<?= $employee['employment_status'] === 'permanent' ? 'success' : 'info' ?>"><?= ucfirst($employee['employment_status']) ?></span>
                </div>
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <div class="h6 mb-0"><?= $employee['face_enrolled'] ? 'Yes' : 'No' ?></div>
                        <small class="text-muted">Face Enrolled</small>
                    </div>
                    <div class="col-6">
                        <div class="h6 mb-0"><?= $employee['department_name'] ?? '-' ?></div>
                        <small class="text-muted">Department</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-telephone me-2"></i>Contact Information</div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Email</small>
                    <div><?= htmlspecialchars($employee['email'] ?: '-') ?></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Phone</small>
                    <div><?= htmlspecialchars($employee['phone'] ?: '-') ?></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Address</small>
                    <div><?= htmlspecialchars($employee['address'] ?: '-') ?></div>
                    <small class="text-muted"><?= htmlspecialchars(trim(($employee['city'] ?? '') . ', ' . ($employee['country'] ?? ''), ', ')) ?></small>
                </div>
                <hr>
                <div class="mb-2">
                    <small class="text-muted d-block">Emergency Contact</small>
                    <div><?= htmlspecialchars($employee['emergency_contact_name'] ?: '-') ?> <?= $employee['emergency_contact_relation'] ? "({$employee['emergency_contact_relation']})" : '' ?></div>
                    <div class="text-sm"><?= htmlspecialchars($employee['emergency_contact_phone'] ?: '-') ?></div>
                </div>
            </div>
        </div>

        <!-- Identity -->
        <div class="card">
            <div class="card-header"><i class="bi bi-person-vcard me-2"></i>Identity</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <small class="text-muted d-block">National ID</small>
                        <div><?= htmlspecialchars($employee['national_id'] ?: '-') ?></div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Passport</small>
                        <div><?= htmlspecialchars($employee['passport_number'] ?: '-') ?></div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Gender</small>
                        <div><?= $employee['gender'] ? ucfirst($employee['gender']) : '-' ?></div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Date of Birth</small>
                        <div><?= $employee['date_of_birth'] ?: '-' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Employment Summary -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-briefcase me-2"></i>Employment Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <small class="text-muted d-block">Department</small>
                        <div class="fw-600"><?= htmlspecialchars($employee['department_name'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Branch</small>
                        <div class="fw-600"><?= htmlspecialchars($employee['branch_name'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Shift</small>
                        <div class="fw-600"><?= htmlspecialchars($employee['shift_name'] ?? '-') ?></div>
                        <?php if ($employee['start_time']): ?>
                            <small class="text-muted"><?= $employee['start_time'] ?> - <?= $employee['end_time'] ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Employment Type</small>
                        <div class="fw-600"><?= ucfirst(str_replace('_', ' ', $employee['employment_type'])) ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Date Joined</small>
                        <div class="fw-600"><?= date('M d, Y', strtotime($employee['date_joined'])) ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Status</small>
                        <div class="fw-600"><?= ucfirst($employee['employment_status']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Salary</small>
                        <div class="fw-600">$<?= number_format($employee['salary'], 2) ?></div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Allowance</small>
                        <div class="fw-600">$<?= number_format($employee['allowance'], 2) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Face Recognition Status -->
        <div class="card mb-3">
            <div class="card-header">
                <span><i class="bi bi-camera-video me-2"></i>Face Recognition Data</span>
                <?php if (Auth::can('manage_face_data')): ?>
                <a href="<?= BASE_URL ?>/face/enroll/<?= $employee['id'] ?>" class="btn btn-sm btn-light">
                    <?= $employee['face_enrolled'] ? 'Re-enroll' : 'Enroll Now' ?>
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($faceData): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> Face recognition data enrolled successfully.
                    </div>
                    <div class="row g-2">
                        <?php foreach ($faceData as $fd): ?>
                        <div class="col-md-3">
                            <div class="border rounded p-2 text-center">
                                <i class="bi bi-camera-video-fill text-success display-6"></i>
                                <div class="text-sm mt-1"><?= htmlspecialchars($fd['label'] ?: 'Capture') ?></div>
                                <small class="text-muted"><?= date('M d, Y', strtotime($fd['captured_at'])) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-camera-video display-4 text-muted"></i>
                        <p class="text-muted mt-2">No face data enrolled yet.</p>
                        <?php if (Auth::can('manage_face_data')): ?>
                        <a href="<?= BASE_URL ?>/face/enroll/<?= $employee['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-camera-video"></i> Enroll Face
                        </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Attendance -->
        <div class="card mb-3">
            <div class="card-header">
                <span><i class="bi bi-calendar-check me-2"></i>Recent Attendance (Last 10)</span>
                <a href="<?= BASE_URL ?>/attendance" class="btn btn-sm btn-light">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr><th>Date</th><th>Check In</th><th>Check Out</th><th>Hours</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceHistory as $a): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($a['attendance_date'])) ?></td>
                                <td><?= $a['check_in'] ? date('H:i', strtotime($a['check_in'])) : '-' ?></td>
                                <td><?= $a['check_out'] ? date('H:i', strtotime($a['check_out'])) : '-' ?></td>
                                <td><?= $a['working_hours'] ?: '-' ?></td>
                                <td><span class="badge bg-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($attendanceHistory)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No attendance records</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="card">
            <div class="card-header"><i class="bi bi-file-earmark me-2"></i>Documents</div>
            <div class="card-body">
                <?php if ($documents): ?>
                    <div class="list-group">
                        <?php foreach ($documents as $doc): ?>
                        <a href="<?= UPLOAD_URL . '/' . $doc['file_path'] ?>" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="bi bi-file-earmark-text me-3 fs-4 text-primary"></i>
                            <div class="flex-grow-1">
                                <div class="fw-600"><?= htmlspecialchars($doc['title']) ?></div>
                                <small class="text-muted"><?= ucfirst($doc['document_type']) ?> · <?= date('M d, Y', strtotime($doc['created_at'])) ?></small>
                            </div>
                            <i class="bi bi-download"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No documents uploaded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
