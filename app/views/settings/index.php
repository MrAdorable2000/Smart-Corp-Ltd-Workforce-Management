<?php /** @var array $data */
$groupLabels = [
    'general' => 'General',
    'security' => 'Security',
    'face' => 'Face Recognition',
    'attendance' => 'Attendance',
    'email' => 'Email (SMTP)',
    'sms' => 'SMS Gateway',
    'whatsapp' => 'WhatsApp API'
];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">System Settings</h4>
</div>

<!-- ============ SYSTEM / COMPANY INFO (EDITABLE) ============ -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-building-fill text-primary me-2"></i>System &amp; Company Information
        <small class="text-muted">(shown on home page and throughout the system)</small>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/settings/update" enctype="multipart/form-data">
            <?= $csrf ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label required">System / Company Name</label>
                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($company['name'] ?? 'Smart Corp Ltd') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tax ID</label>
                    <input type="text" name="tax_id" class="form-control" value="<?= htmlspecialchars($company['tax_id'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($company['email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($company['phone'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($company['city'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($company['state'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($company['country'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" class="form-control" value="<?= htmlspecialchars($company['currency'] ?? 'USD') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Timezone</label>
                    <input type="text" name="timezone" class="form-control" value="<?= htmlspecialchars($company['timezone'] ?? 'UTC') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Website</label>
                    <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($company['website'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Logo</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Update System Information</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============ OTHER SETTINGS ============ -->
<form method="POST" action="<?= BASE_URL ?>/settings/update">
    <?= $csrf ?>
    <div class="row">
        <div class="col-md-3">
            <div class="list-group" id="settingsTabs">
                <?php $i = 0; foreach ($grouped as $group => $settings): ?>
                <a href="#<?= $group ?>" class="list-group-item list-group-item-action <?= $i === 0 ? 'active' : '' ?>" data-bs-toggle="list">
                    <i class="bi bi-<?= $group === 'general' ? 'gear' : ($group === 'security' ? 'shield-lock' : ($group === 'face' ? 'camera-video' : ($group === 'email' ? 'envelope' : ($group === 'sms' ? 'phone' : 'whatsapp')))) ?>"></i>
                    <?= $groupLabels[$group] ?? ucfirst($group) ?>
                </a>
                <?php $i++; endforeach; ?>
            </div>
        </div>
        <div class="col-md-9">
            <div class="tab-content">
                <?php $i = 0; foreach ($grouped as $group => $settings): ?>
                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="<?= $group ?>">
                    <div class="card">
                        <div class="card-header"><?= $groupLabels[$group] ?? ucfirst($group) ?> Settings</div>
                        <div class="card-body">
                            <?php foreach ($settings as $s): ?>
                            <div class="mb-3">
                                <label class="form-label"><?= ucfirst(str_replace('_', ' ', $s['setting_key'])) ?></label>
                                <?php if ($s['data_type'] === 'boolean'): ?>
                                <select name="<?= $s['setting_key'] ?>" class="form-select">
                                    <option value="1" <?= $s['setting_value'] === '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= $s['setting_value'] === '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                                <?php elseif ($s['data_type'] === 'text' || strlen($s['setting_value'] ?? '') > 80): ?>
                                <textarea name="<?= $s['setting_key'] ?>" class="form-control" rows="2"><?= htmlspecialchars($s['setting_value'] ?? '') ?></textarea>
                                <?php else: ?>
                                <input type="text" name="<?= $s['setting_key'] ?>" class="form-control" value="<?= htmlspecialchars($s['setting_value'] ?? '') ?>">
                                <?php endif; ?>
                                <small class="text-muted"><?= htmlspecialchars($s['description'] ?? '') ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php $i++; endforeach; ?>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save All Settings</button>
            </div>
        </div>
    </div>
</form>
