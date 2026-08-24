<?php /** @var array $data */
$actionIcons = [
    'create'    => ['bi-plus-circle-fill', 'success'],
    'update'    => ['bi-pencil-fill', 'primary'],
    'delete'    => ['bi-trash-fill', 'danger'],
    'approve'   => ['bi-check-circle-fill', 'success'],
    'reject'    => ['bi-x-circle-fill', 'danger'],
    'check_in'  => ['bi-door-open', 'success'],
    'check_out' => ['bi-door-closed', 'primary'],
];
$iconInfo = $actionIcons[$change['action']] ?? ['bi-circle', 'secondary'];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Change Details #<?= $change['id'] ?></h4>
    <a href="<?= BASE_URL ?>/database-activity" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <!-- Change Summary -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Change Summary</div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <th style="width:130px">Change ID</th>
                        <td><code>#<?= $change['id'] ?></code></td>
                    </tr>
                    <tr>
                        <th>Action</th>
                        <td>
                            <span class="badge bg-<?= $iconInfo[1] ?>">
                                <i class="bi <?= $iconInfo[0] ?>"></i>
                                <?= ucfirst(str_replace('_', ' ', $change['action'])) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Table</th>
                        <td><code class="text-primary"><?= htmlspecialchars($change['module']) ?></code></td>
                    </tr>
                    <tr>
                        <th>Timestamp</th>
                        <td><?= date('M d, Y H:i:s', strtotime($change['created_at'])) ?></td>
                    </tr>
                    <tr>
                        <th>Performed By</th>
                        <td>
                            <?php if ($change['user_name']): ?>
                                <?= htmlspecialchars($change['user_name']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($change['user_email']) ?></small>
                            <?php else: ?>
                                <span class="text-muted">System / Anonymous</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>IP Address</th>
                        <td><code><?= htmlspecialchars($change['ip_address']) ?></code></td>
                    </tr>
                    <tr>
                        <th>HTTP Method</th>
                        <td><span class="badge bg-info"><?= htmlspecialchars($change['http_method']) ?></span></td>
                    </tr>
                    <tr>
                        <th>Request URL</th>
                        <td class="text-sm"><code><?= htmlspecialchars($change['request_url']) ?></code></td>
                    </tr>
                    <tr>
                        <th>Severity</th>
                        <td><span class="badge bg-<?= $change['severity'] === 'critical' ? 'danger' : ($change['severity'] === 'warning' ? 'warning' : 'info') ?>"><?= ucfirst($change['severity']) ?></span></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Description -->
        <div class="card">
            <div class="card-header"><i class="bi bi-chat-text me-2"></i>Description</div>
            <div class="card-body">
                <p class="mb-0"><?= htmlspecialchars($change['description']) ?></p>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <?php if ($change['action'] === 'update' && !empty($diff)): ?>
        <!-- Field-level diff for updates -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-arrow-left-right me-2"></i>Field Changes (<?= count($diff) ?> field<?= count($diff) !== 1 ? 's' : '' ?> changed)
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Field Name</th>
                            <th>Old Value</th>
                            <th>New Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diff as $d): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($d['field']) ?></code></td>
                            <td class="text-danger text-sm">
                                <?php if ($d['old'] === null): ?>
                                    <span class="text-muted">NULL</span>
                                <?php elseif (is_array($d['old'])): ?>
                                    <pre class="mb-0 small"><?= htmlspecialchars(json_encode($d['old'], JSON_PRETTY_PRINT)) ?></pre>
                                <?php else: ?>
                                    <?= htmlspecialchars(substr((string)$d['old'], 0, 200)) ?>
                                    <?= strlen((string)$d['old']) > 200 ? '...' : '' ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-success text-sm">
                                <?php if ($d['new'] === '[removed]'): ?>
                                    <span class="text-muted">[field removed]</span>
                                <?php elseif (is_array($d['new'])): ?>
                                    <pre class="mb-0 small"><?= htmlspecialchars(json_encode($d['new'], JSON_PRETTY_PRINT)) ?></pre>
                                <?php else: ?>
                                    <?= htmlspecialchars(substr((string)$d['new'], 0, 200)) ?>
                                    <?= strlen((string)$d['new']) > 200 ? '...' : '' ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($newValues): ?>
        <!-- New Values (for inserts) -->
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <i class="bi bi-plus-circle me-2"></i>New Record Values
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Field</th><th>Value</th></tr></thead>
                    <tbody>
                        <?php foreach ($newValues as $field => $value): ?>
                        <tr>
                            <td style="width:40%"><code><?= htmlspecialchars($field) ?></code></td>
                            <td class="text-sm">
                                <?php if ($value === null): ?>
                                    <span class="text-muted">NULL</span>
                                <?php elseif (is_array($value)): ?>
                                    <pre class="mb-0 small"><?= htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) ?></pre>
                                <?php else: ?>
                                    <?= htmlspecialchars(substr((string)$value, 0, 300)) ?>
                                    <?= strlen((string)$value) > 300 ? '...' : '' ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($oldValues && $change['action'] === 'delete'): ?>
        <!-- Old Values (for deletes) -->
        <div class="card mb-3">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-archive me-2"></i>Deleted Record (was)
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Field</th><th>Value</th></tr></thead>
                    <tbody>
                        <?php foreach ($oldValues as $field => $value): ?>
                        <tr>
                            <td style="width:40%"><code><?= htmlspecialchars($field) ?></code></td>
                            <td class="text-sm">
                                <?php if ($value === null): ?>
                                    <span class="text-muted">NULL</span>
                                <?php elseif (is_array($value)): ?>
                                    <pre class="mb-0 small"><?= htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) ?></pre>
                                <?php else: ?>
                                    <?= htmlspecialchars(substr((string)$value, 0, 300)) ?>
                                    <?= strlen((string)$value) > 300 ? '...' : '' ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($oldValues && $newValues && empty($diff)): ?>
        <!-- Full old values for update (fallback if diff is empty) -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Previous Values</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Field</th><th>Old Value</th></tr></thead>
                    <tbody>
                        <?php foreach ($oldValues as $field => $value): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($field) ?></code></td>
                            <td class="text-muted text-sm"><?= htmlspecialchars(substr((string)$value, 0, 200)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
