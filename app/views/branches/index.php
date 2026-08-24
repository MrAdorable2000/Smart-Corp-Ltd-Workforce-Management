<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Branch Management</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Add Branch</button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Code</th><th>Name</th><th>Email</th><th>Phone</th><th>City</th><th>Coordinates</th><th>Geofence</th><th>Employees</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($branches as $b): ?>
                    <tr>
                        <td><span class="badge bg-primary"><?= htmlspecialchars($b['code'] ?: '-') ?></span></td>
                        <td class="fw-600"><?= htmlspecialchars($b['name']) ?></td>
                        <td class="text-sm"><?= htmlspecialchars($b['email'] ?: '-') ?></td>
                        <td class="text-sm"><?= htmlspecialchars($b['phone'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($b['city'] ?? '-') ?></td>
                        <td class="text-sm"><?= $b['latitude'] ? number_format($b['latitude'], 4) . ', ' . number_format($b['longitude'], 4) : '-' ?></td>
                        <td><span class="badge bg-info"><?= $b['geofence_radius'] ?>m</span></td>
                        <td><span class="badge bg-light text-dark"><?= $b['employee_count'] ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-light btn-icon-sm edit-btn"
                                data-id="<?= $b['id'] ?>"
                                data-name="<?= htmlspecialchars($b['name']) ?>"
                                data-code="<?= htmlspecialchars($b['code']) ?>"
                                data-email="<?= htmlspecialchars($b['email']) ?>"
                                data-phone="<?= htmlspecialchars($b['phone']) ?>"
                                data-address="<?= htmlspecialchars($b['address']) ?>"
                                data-city="<?= htmlspecialchars($b['city']) ?>"
                                data-country="<?= htmlspecialchars($b['country']) ?>"
                                data-lat="<?= $b['latitude'] ?>"
                                data-lng="<?= $b['longitude'] ?>"
                                data-radius="<?= $b['geofence_radius'] ?>"><i class="bi bi-pencil"></i></button>
                            <form method="POST" action="<?= BASE_URL ?>/branches/<?= $b['id'] ?>/delete" class="d-inline">
                                <?= $csrf ?>
                                <button class="btn btn-sm btn-light btn-icon-sm text-danger confirm-delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Branch Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/branches/store">
                <?= $csrf ?>
                <div class="modal-header">
                    <h5 class="modal-title">Add New Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="United States">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Geofence Radius (m)</label>
                            <input type="number" name="geofence_radius" class="form-control" value="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Latitude</label>
                            <input type="text" name="latitude" class="form-control" placeholder="e.g. 40.7127760">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Longitude</label>
                            <input type="text" name="longitude" class="form-control" placeholder="e.g. -74.0059740">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Branch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Branch Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" id="editForm">
                <?= $csrf ?>
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Name</label>
                            <input type="text" name="name" class="form-control" required id="e_name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" id="e_code">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" id="e_email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" id="e_phone">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2" id="e_address"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" id="e_city">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" id="e_country">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Geofence Radius (m)</label>
                            <input type="number" name="geofence_radius" class="form-control" id="e_radius">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Latitude</label>
                            <input type="text" name="latitude" class="form-control" id="e_lat">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Longitude</label>
                            <input type="text" name="longitude" class="form-control" id="e_lng">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Branch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('.edit-btn').on('click', function() {
    const id = $(this).data('id');
    $('#editForm').attr('action', '<?= BASE_URL ?>/branches/' + id + '/update');
    $('#e_name').val($(this).data('name'));
    $('#e_code').val($(this).data('code'));
    $('#e_email').val($(this).data('email'));
    $('#e_phone').val($(this).data('phone'));
    $('#e_address').val($(this).data('address'));
    $('#e_city').val($(this).data('city'));
    $('#e_country').val($(this).data('country'));
    $('#e_lat').val($(this).data('lat'));
    $('#e_lng').val($(this).data('lng'));
    $('#e_radius').val($(this).data('radius'));
    new bootstrap.Modal('#editModal').show();
});
</script>
