<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?= $pageTitle ?></h4>
    <a href="<?= BASE_URL ?>/shifts" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/shifts/<?= $shift ? $shift['id'].'/update' : 'store' ?>">
                    <?= $csrf ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Shift Name</label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($shift['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($shift['code'] ?? '') ?>" style="text-transform:uppercase">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Start Time</label>
                            <input type="time" name="start_time" class="form-control" required value="<?= $shift['start_time'] ?? '08:00' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">End Time</label>
                            <input type="time" name="end_time" class="form-control" required value="<?= $shift['end_time'] ?? '17:00' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Working Hours/Day</label>
                            <input type="number" step="0.01" name="working_hours_per_day" class="form-control" value="<?= $shift['working_hours_per_day'] ?? 8.00 ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Grace Period (min)</label>
                            <input type="number" name="grace_period_minutes" class="form-control" value="<?= $shift['grace_period_minutes'] ?? 15 ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Late Threshold (min)</label>
                            <input type="number" name="late_threshold_minutes" class="form-control" value="<?= $shift['late_threshold_minutes'] ?? 15 ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Early Leave (min)</label>
                            <input type="number" name="early_leave_threshold_minutes" class="form-control" value="<?= $shift['early_leave_threshold_minutes'] ?? 15 ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Overtime Rate</label>
                            <input type="number" step="0.01" name="overtime_rate" class="form-control" value="<?= $shift['overtime_rate'] ?? 1.50 ?>">
                        </div>
                        <div class="col-md-8 d-flex align-items-end gap-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="overtime_eligible" value="1" id="ot" <?= ($shift['overtime_eligible'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ot">Overtime Eligible</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_night_shift" value="1" id="ns" <?= ($shift['is_night_shift'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ns">Night Shift</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_flexible" value="1" id="fl" <?= ($shift['is_flexible'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="fl">Flexible</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($shift['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= $shift ? 'Update' : 'Create' ?></button>
                            <a href="<?= BASE_URL ?>/shifts" class="btn btn-light">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Pre-configured Shifts</div>
            <div class="card-body">
                <p class="small text-muted mb-2">Standard shifts:</p>
                <ul class="list-unstyled small">
                    <li class="mb-2"><i class="bi bi-sun text-warning"></i> <strong>Morning Shift</strong><br><small class="text-muted">8:00 AM - 5:00 PM</small></li>
                    <li class="mb-2"><i class="bi bi-sunset text-primary"></i> <strong>Evening Shift</strong><br><small class="text-muted">2:00 PM - 10:00 PM</small></li>
                    <li class="mb-2"><i class="bi bi-moon text-dark"></i> <strong>Night Shift</strong><br><small class="text-muted">10:00 PM - 6:00 AM</small></li>
                    <li class="mb-2"><i class="bi bi-arrow-left-right text-info"></i> <strong>Flexible Shift</strong><br><small class="text-muted">9:00 AM - 6:00 PM (any 8h)</small></li>
                </ul>
            </div>
        </div>
    </div>
</div>
