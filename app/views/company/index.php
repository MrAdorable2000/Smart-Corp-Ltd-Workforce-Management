<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Company Profile</h4>
</div>

<form method="POST" action="<?= BASE_URL ?>/company/update" enctype="multipart/form-data">
    <?= $csrf ?>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="mb-3">
                        <?php if (!empty($company['logo']) && file_exists(UPLOAD_PATH . '/' . $company['logo'])): ?>
                            <img src="<?= UPLOAD_URL . '/' . $company['logo'] ?>" alt="Logo" style="width:120px;height:120px;object-fit:contain;">
                        <?php else: ?>
                            <div class="display-1 text-muted"><i class="bi bi-building"></i></div>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Company Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label required">Company Name</label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($company['name']) ?>">
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
                        <div class="col-12">
                            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Company</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
