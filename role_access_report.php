<?php
include 'database.php';

/*
|--------------------------------------------------------------------------
| HANDLE AJAX TOGGLE REQUEST
|--------------------------------------------------------------------------
*/
if (isset($_POST['action']) && $_POST['action'] === 'toggle_access') {
    header('Content-Type: application/json');
    
    try {
        $menuId = (int) $_POST['menu_id'];
        $role = mysqli_real_escape_string($conn, trim($_POST['role']));
        
        if (!$menuId || !$role) {
            throw new Exception('Invalid menu ID or role');
        }
        
        // Get current roles
        $query = "SELECT id, allowed_roles FROM sidebar_menus WHERE id = $menuId";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception('Query failed: ' . mysqli_error($conn));
        }
        
        $row = mysqli_fetch_assoc($result);
        
        if (!$row) {
            throw new Exception('Menu not found');
        }
        
        // Parse current roles
        $currentRoles = array_map('trim', explode(',', $row['allowed_roles']));
        $currentRoles = array_filter($currentRoles); // Remove empty values
        
        // Toggle the role
        $hasRole = in_array($role, $currentRoles);
        
        if ($hasRole) {
            // Remove role
            $currentRoles = array_values(array_filter($currentRoles, function($r) use ($role) {
                return trim($r) !== $role;
            }));
        } else {
            // Add role
            $currentRoles[] = $role;
        }
        
        // Update database with cleaned roles
        $newRoles = implode(', ', array_unique(array_filter($currentRoles)));
        $newRoles = mysqli_real_escape_string($conn, $newRoles);
        
        $updateQuery = "UPDATE sidebar_menus SET allowed_roles = '$newRoles' WHERE id = $menuId";
        $updateResult = mysqli_query($conn, $updateQuery);
        
        if (!$updateResult) {
            throw new Exception('Update failed: ' . mysqli_error($conn));
        }
        
        echo json_encode([
            'success' => true,
            'hasAccess' => !$hasRole,
            'newRoles' => $newRoles,
            'message' => 'Permission updated successfully'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

/*
|--------------------------------------------------------------------------
| FETCH ALL MENUS WITH ROLES
|--------------------------------------------------------------------------
*/
$menuQuery = mysqli_query($conn, "
    SELECT 
        sm.id,
        sm.menu_name,
        sm.menu_link,
        sm.allowed_roles,
        sg.group_name
    FROM sidebar_menus sm
    LEFT JOIN sidebar_groups sg ON sm.group_id = sg.id
    ORDER BY sm.menu_name ASC
");

// Get all unique roles. Keep core roles visible even before a menu uses them.
$allRoles = [
    'owner',
    'management',
    'ceo',
    'project_manager',
    'finance',
    'engineer',
    'supplier',
    'contractor_staff',
    'system_admin'
];
$menus = [];

while ($row = mysqli_fetch_assoc($menuQuery)) {
    $menus[] = $row;
    $roles = array_map('trim', explode(',', $row['allowed_roles']));
    foreach ($roles as $role) {
        if (!in_array($role, $allRoles)) {
            $allRoles[] = $role;
        }
    }
}

sort($allRoles);
?>

<div class="container-fluid">

    <!-- HEADER SECTION -->
    <div class="bg-gradient py-5 mb-5 rounded-bottom-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="text-white mb-2">
                        <i class="bi bi-shield-lock"></i>
                        Role Access Report
                    </h1>
                    <p class="text-white-50 mb-0">
                        <i class="bi bi-info-circle"></i>
                        View and manage role-based page access permissions
                    </p>
                </div>
                <div class="d-none d-lg-block">
                    <i class="bi bi-lock-fill text-white" style="font-size: 4rem; opacity: 0.2;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="px-4">

        <!-- PAGES BY ROLE ACCESS TABLE -->
        <div class="mb-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white py-4 px-4">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>
                        Pages by Role Access
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="70" class="text-center">
                                        <i class="bi bi-hash"></i>
                                    </th>
                                    <th>Page Name</th>
                                    <th width="200">Link</th>
                                    <th width="180">Group</th>
                                    <th>Allowed Roles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menus as $index => $menu): ?>
                                    <tr class="border-bottom">
                                        <td class="text-center text-muted">
                                            <small class="fw-bold"><?= $menu['id'] ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">
                                                <?= htmlspecialchars($menu['menu_name']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <code class="bg-light px-3 py-2 rounded-2 d-inline-block">
                                                <?= htmlspecialchars($menu['menu_link']) ?>
                                            </code>
                                        </td>
                                        <td>
                                            <?php if ($menu['group_name']): ?>
                                                <span class="badge bg-info text-dark">
                                                    <i class="bi bi-folder me-1"></i>
                                                    <?= htmlspecialchars($menu['group_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-50">
                                                    —
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php
                                                $roles = array_map('trim', explode(',', $menu['allowed_roles']));
                                                foreach ($roles as $role):
                                                ?>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                        <?= htmlspecialchars(trim($role)) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROLE ACCESS MATRIX TABLE -->
        <div class="mb-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-success text-white py-4 px-4">
                    <h5 class="mb-0">
                        <i class="bi bi-table me-2"></i>
                        Role Access Matrix <br><small class="text-white text-capitalize" style="font-size: 10px;">(after you activate it or deactivate it, please refresh the page to see the changes)</small>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="position-sticky top-0 z-index-5">
                                    <th style="min-width: 280px;" class="bg-light fw-bold position-sticky start-0 top-0 z-index-5">
                                        <i class="bi bi-file-earmark me-2"></i>
                                        Page / Link
                                    </th>
                                    <?php foreach ($allRoles as $role): ?>
                                        <th class="text-center bg-light fw-bold" style="min-width: 140px;">
                                            <div class="text-truncate" title="<?= htmlspecialchars($role) ?>">
                                                <?= htmlspecialchars($role) ?>
                                            </div>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menus as $menu): ?>
                                    <tr>
                                        <td class=" position-sticky start-0 top-0 z-index-5">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($menu['menu_name']) ?></div>
                                            <small class="text-muted d-block mt-1">
                                                <code><?= htmlspecialchars($menu['menu_link']) ?></code>
                                            </small>
                                        </td>
                                        <?php
                                        $menuRoles = array_map('trim', explode(',', $menu['allowed_roles']));
                                        foreach ($allRoles as $role):
                                            $hasAccess = in_array($role, $menuRoles);
                                        ?>
                                            <td class="text-center">
                                                <button 
                                                    type="button"
                                                    class="toggle-access-btn badge px-3 py-2 rounded-pill border-0 cursor-pointer"
                                                    data-menu-id="<?= $menu['id'] ?>"
                                                    data-role="<?= htmlspecialchars($role) ?>"
                                                    data-has-access="<?= $hasAccess ? '1' : '0' ?>"
                                                    style="<?= $hasAccess ? 'background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc !important;' : 'background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7 !important;' ?>"
                                                >
                                                    <i class="bi <?= $hasAccess ? 'bi-check-circle' : 'bi-x-circle' ?> me-1"></i>
                                                    <span class="d-none d-sm-inline"><?= $hasAccess ? 'Access' : 'Denied' ?></span>
                                                </button>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROLES SUMMARY -->
        <div class="mb-5">
            <h5 class="mb-4 fw-bold">
                <i class="bi bi-people"></i>
                Role Summary
            </h5>
            <div class="row g-4">
                <?php
                $colorMap = [
                    'system_admin' => ['bg-danger', 'text-danger', 'bi-shield-exclamation'],
                    'admin' => ['bg-warning', 'text-warning', 'bi-shield-check'],
                    'management' => ['bg-info', 'text-info', 'bi-briefcase'],
                    'engineer' => ['bg-success', 'text-success', 'bi-tools'],
                    'staff' => ['bg-primary', 'text-primary', 'bi-person'],
                ];

                foreach ($allRoles as $index => $role):
                    $colors = $colorMap[$role] ?? ['bg-secondary', 'text-secondary', 'bi-person-circle'];
                ?>
                    <div class="col-lg-6 col-xxl-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="<?= $colors[0] ?> bg-opacity-10 rounded-circle p-3 me-3">
                                        <i class="bi <?= $colors[2] ?> <?= $colors[1] ?> fs-5"></i>
                                    </div>
                                    <h5 class="mb-0 fw-bold">
                                        <?= htmlspecialchars($role) ?>
                                    </h5>
                                </div>

                                <div class="role-access-list">
                                    <?php
                                    $rolePages = array_filter($menus, function ($menu) use ($role) {
                                        return in_array($role, array_map('trim', explode(',', $menu['allowed_roles'])));
                                    });

                                    if (count($rolePages) > 0):
                                    ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-3 fw-semibold">
                                                <i class="bi bi-list-check me-2"></i>
                                                Accessible Pages (<?= count($rolePages) ?>)
                                            </small>
                                            <ul class="list-unstyled">
                                                <?php foreach ($rolePages as $page): ?>
                                                    <li class="mb-2 ps-2 border-start border-3 border-primary">
                                                        <small class="d-block">
                                                            <strong><?= htmlspecialchars($page['menu_name']) ?></strong>
                                                        </small>
                                                        <code class="text-muted d-block mt-1">
                                                            <?= htmlspecialchars($page['menu_link']) ?>
                                                        </code>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>

                                        <div class="mt-4 pt-3 border-top">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class=" text-white ">
                                                    <i class="bi bi-graph-up me-1"></i>
                                                    Total Access
                                                </small>
                                                <span class="badge <?= $colors[0] ?> text-white rounded-pill px-3 py-2">
                                                    <?= count($rolePages) ?> Pages
                                                </span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning-subtle rounded-3 border-warning mb-0">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            <small>No pages assigned to this role</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>


    <style>
        :root {
            --bs-border-radius-lg: 1rem;
        }

        body {
            background-color: #f8f9fa;
        }

        .bg-gradient {
            background-attachment: fixed;
        }

        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.15) !important;
        }

        .table {
            font-size: 0.95rem;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        code {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }

        .badge {
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .table-responsive {
            border-radius: 0 0 1rem 1rem;
        }

        .text-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        h1,
        h5 {
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .border-start {
            transition: border-color 0.3s ease;
        }

        .ps-2 {
            padding-left: 0.75rem !important;
        }

        .rounded-4 {
            border-radius: 1.5rem;
        }

        .rounded-3 {
            border-radius: 0.75rem;
        }

        .rounded-2 {
            border-radius: 0.5rem;
        }

        .rounded-pill {
            border-radius: 50rem;
        }

        @media (max-width: 768px) {
            .table {
                font-size: 0.85rem;
            }

            code {
                font-size: 0.75rem;
            }

            .badge {
                padding: 0.35rem 0.55rem !important;
                font-size: 0.75rem;
            }
        }
    </style>

    <!-- TOGGLE ACCESS SCRIPT -->
    <script>
        document.querySelectorAll('.toggle-access-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const menuId = this.dataset.menuId;
                const role = this.dataset.role;
                const hasAccess = this.dataset.hasAccess === '1';
                const btn = this;

                // Add loading state
                btn.disabled = true;
                btn.style.opacity = '0.6';
                btn.innerHTML = '<i class="bi bi-arrow-repeat spin me-1"></i><span class="d-none d-sm-inline">Updating...</span>';

                // Send AJAX request
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'toggle_access',
                        menu_id: menuId,
                        role: role
                    })
                })
                .then(response => {
                    // Check if response is ok
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    // Parse JSON
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse response:', text);
                        throw new Error('Server returned invalid response: ' + text);
                    }
                })
                .then(data => {
                    if (data.success) {
                        // Update button state
                        const newHasAccess = !hasAccess;
                        btn.dataset.hasAccess = newHasAccess ? '1' : '0';

                        if (newHasAccess) {
                            // Show Access
                            btn.style.backgroundColor = '#d1e7dd';
                            btn.style.color = '#0f5132';
                            btn.style.borderColor = '#badbcc';
                            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i><span class="d-none d-sm-inline">Access</span>';
                        } else {
                            // Show Denied
                            btn.style.backgroundColor = '#f8d7da';
                            btn.style.color = '#842029';
                            btn.style.borderColor = '#f5c2c7';
                            btn.innerHTML = '<i class="bi bi-x-circle me-1"></i><span class="d-none d-sm-inline">Denied</span>';
                        }

                        // Show success toast
                        showToast('Permission updated successfully!', 'success');
                    } else {
                        const errorMsg = data.error || 'Unknown error occurred';
                        console.error('Error:', errorMsg);
                        showToast('Error: ' + errorMsg, 'danger');
                        // Restore button
                        btn.innerHTML = hasAccess 
                            ? '<i class="bi bi-check-circle me-1"></i><span class="d-none d-sm-inline">Access</span>'
                            : '<i class="bi bi-x-circle me-1"></i><span class="d-none d-sm-inline">Denied</span>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error.message);
                    showToast('Error: ' + error.message, 'danger');
                    // Restore button
                    btn.innerHTML = hasAccess 
                        ? '<i class="bi bi-check-circle me-1"></i><span class="d-none d-sm-inline">Access</span>'
                        : '<i class="bi bi-x-circle me-1"></i><span class="d-none d-sm-inline">Denied</span>';
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                });
            });
        });

        // Toast notification function
        function showToast(message, type = 'info') {
            const toastHtml = `
                <div class="toast-container position-fixed bottom-0 end-0 p-3">
                    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header bg-${type} text-white border-0">
                            <strong class="me-auto">
                                <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'danger' ? 'x-circle' : 'info-circle'} me-2"></i>
                                ${type.charAt(0).toUpperCase() + type.slice(1)}
                            </strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                        </div>
                        <div class="toast-body">
                            ${message}
                        </div>
                    </div>
                </div>
            `;

            const container = document.querySelector('.toast-container') || document.createElement('div');
            container.innerHTML = toastHtml;
            if (!document.querySelector('.toast-container')) {
                document.body.appendChild(container);
            }

            // Auto remove after 4 seconds
            setTimeout(() => {
                const toast = document.querySelector('.toast');
                if (toast) toast.remove();
            }, 4000);
        }

        // Add spin animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                to {
                    transform: rotate(360deg);
                }
            }
            .spin {
                animation: spin 1s linear infinite;
            }
            .toggle-access-btn {
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .toggle-access-btn:hover:not(:disabled) {
                transform: scale(1.05);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            }
            .toggle-access-btn:disabled {
                cursor: not-allowed;
            }
        `;
        document.head.appendChild(style);
    </script>
