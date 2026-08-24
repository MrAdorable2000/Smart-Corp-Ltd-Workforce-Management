<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Holiday Configuration</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Add Holiday</button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Name</th><th>Type</th><th>Date</th><th>End Date</th><th>Recurring</th><th>Country</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($holidays as $h):
                        $typeColors = ['public' => 'primary', 'religious' => 'info', 'company' => 'success', 'national' => 'warning'];
                    ?>
                    <tr>
                        <td class="fw-600"><?= htmlspecialchars($h['name']) ?><br><small class="text-muted"><?= htmlspecialchars($h['description'] ?: '') ?></small></td>
                        <td><span class="badge bg-<?= $typeColors[$h['type']] ?? 'secondary' ?>"><?= ucfirst($h['type']) ?></span></td>
                        <td><?= date('M d, Y', strtotime($h['holiday_date'])) ?></td>
                        <td><?= $h['end_date'] ? date('M d, Y', strtotime($h['end_date'])) : '-' ?></td>
                        <td><?= $h['is_recurring'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>' ?></td>
                        <td><?= htmlspecialchars($h['country'] ?: '-') ?></td>
                        <td>
                            <button class="btn btn-sm btn-light btn-icon-sm edit-holiday"
                                data-id="<?= $h['id'] ?>"
                                data-name="<?= htmlspecialchars($h['name']) ?>"
                                data-desc="<?= htmlspecialchars($h['description']) ?>"
                                data-date="<?= $h['holiday_date'] ?>"
                                data-end="<?= $h['end_date'] ?>"
                                data-recurring="<?= $h['is_recurring'] ?>"
                                data-type="<?= $h['type'] ?>"
                                data-country="<?= htmlspecialchars($h['country']) ?>"><i class="bi bi-pencil"></i></button>
                            <form method="POST" action="<?= BASE_URL ?>/holidays/<?= $h['id'] ?>/delete" class="d-inline">
                                <?= $csrf ?>
                                <button class="btn btn-sm btn-light btn-icon-sm text-danger confirm-delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($holidays)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No holidays configured</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/holidays/store">
                <?= $csrf ?>
                <div class="modal-header"><h5 class="modal-title">Add Holiday</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label required">Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label required">Start Date</label><input type="date" name="holiday_date" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">End Date (optional)</label><input type="date" name="end_date" class="form-control"></div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="public">Public</option>
                                <option value="religious">Religious</option>
                                <option value="company">Company</option>
                                <option value="national">National</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Country</label><input type="text" name="country" class="form-control" value="United States"></div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_recurring" value="1" id="rec">
                        <label class="form-check-label" for="rec">Recurring (every year)</label>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add Holiday</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" id="editForm">
                <?= $csrf ?>
                <div class="modal-header"><h5 class="modal-title">Edit Holiday</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label required">Name</label><input type="text" name="name" class="form-control" required id="h_name"></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2" id="h_desc"></textarea></div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label required">Start Date</label><input type="date" name="holiday_date" class="form-control" required id="h_date"></div>
                        <div class="col-md-6"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" id="h_end"></div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label">Type</label>
                            <select name="type" class="form-select" id="h_type">
                                <option value="public">Public</option>
                                <option value="religious">Religious</option>
                                <option value="company">Company</option>
                                <option value="national">National</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Country</label><input type="text" name="country" class="form-control" id="h_country"></div>
                    </div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="is_recurring" value="1" id="h_rec"><label class="form-check-label" for="h_rec">Recurring</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update</button></div>
            </form>
        </div>
    </div>
</div>

<script>
$('.edit-holiday').on('click', function() {
    const id = $(this).data('id');
    $('#editForm').attr('action', '<?= BASE_URL ?>/holidays/' + id + '/update');
    $('#h_name').val($(this).data('name'));
    $('#h_desc').val($(this).data('desc'));
    $('#h_date').val($(this).data('date'));
    $('#h_end').val($(this).data('end'));
    $('#h_type').val($(this).data('type'));
    $('#h_country').val($(this).data('country'));
    $('#h_rec').prop('checked', $(this).data('recurring') == 1);
    new bootstrap.Modal('#editModal').show();
});
</script>
