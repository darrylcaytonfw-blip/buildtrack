<?php
include 'database.php';
/*
|--------------------------------------------------------------------------
| DELETE GROUP
|--------------------------------------------------------------------------
*/
if (isset($_GET['delete_group'])) {

    $deleteID = (int) $_GET['delete_group'];

    mysqli_query($conn, "
        DELETE FROM sidebar_groups
        WHERE id = '$deleteID'
    ");

    header("Location: ./?link=sidebar_menu_manager.php&message=Group+deleted+successfully");
    exit;
}

/*
|--------------------------------------------------------------------------
| UPDATE GROUP
|--------------------------------------------------------------------------
*/
if (isset($_POST['update_group'])) {

    $group_id   = (int) $_POST['group_id'];
    $group_name = mysqli_real_escape_string($conn, $_POST['group_name']);
    $group_key  = mysqli_real_escape_string($conn, $_POST['group_key']);
    $group_icon = mysqli_real_escape_string($conn, $_POST['group_icon']);
    $sort_order = (int) $_POST['sort_order'];

    mysqli_query($conn, "
        UPDATE sidebar_groups
        SET
            group_name = '$group_name',
            group_key = '$group_key',
            group_icon = '$group_icon',
            sort_order = '$sort_order'
        WHERE id = '$group_id'
    ");

    header("Location: ./?link=sidebar_menu_manager.php&message=Group+updated+successfully");
    exit;
}
/*
|--------------------------------------------------------------------------
| DELETE MENU
|--------------------------------------------------------------------------
*/
if (isset($_GET['delete'])) {

    $deleteID = (int) $_GET['delete'];

    mysqli_query($conn, "
        DELETE FROM sidebar_menus
        WHERE id = '$deleteID'
    ");

    header("Location: ./?link=sidebar_menu_manager.php&message=Menu+deleted+successfully");
    exit;
}

/*
|--------------------------------------------------------------------------
| UPDATE MENU
|--------------------------------------------------------------------------
*/
if (isset($_POST['update_menu'])) {

    $menu_id       = (int) $_POST['menu_id'];
    $group_id      = !empty($_POST['group_id']) ? (int) $_POST['group_id'] : 'NULL';
    $menu_name     = mysqli_real_escape_string($conn, $_POST['menu_name']);
    $menu_link     = mysqli_real_escape_string($conn, $_POST['menu_link']);
    $menu_icon     = mysqli_real_escape_string($conn, $_POST['menu_icon']);
    $allowed_roles = mysqli_real_escape_string($conn, $_POST['allowed_roles']);
    $sort_order    = (int) $_POST['sort_order'];

    mysqli_query($conn, "
        UPDATE sidebar_menus
        SET
            group_id = $group_id,
            menu_name = '$menu_name',
            menu_link = '$menu_link',
            menu_icon = '$menu_icon',
            allowed_roles = '$allowed_roles',
            sort_order = '$sort_order'
        WHERE id = '$menu_id'
    ");

    header("Location: ./?link=sidebar_menu_manager.php&message=Menu+updated+successfully");
    exit;
}

/*
|--------------------------------------------------------------------------
| ADD GROUP
|--------------------------------------------------------------------------
*/
if (isset($_POST['add_group'])) {

    $group_name = mysqli_real_escape_string($conn, $_POST['group_name']);
    $group_icon = mysqli_real_escape_string($conn, $_POST['group_icon']);
    $group_key  = mysqli_real_escape_string($conn, $_POST['group_key']);
    $sort_order = (int) $_POST['sort_order'];

    mysqli_query($conn, "
        INSERT INTO sidebar_groups
        (
            group_name,
            group_icon,
            group_key,
            sort_order
        )
        VALUES
        (
            '$group_name',
            '$group_icon',
            '$group_key',
            '$sort_order'
        )
    ");

    header("Location: ./?link=sidebar_menu_manager.php&message=Group+added+successfully");
    exit;
}

/*
|--------------------------------------------------------------------------
| ADD MENU
|--------------------------------------------------------------------------
*/
if (isset($_POST['add_menu'])) {

    $group_id      = !empty($_POST['group_id']) ? (int) $_POST['group_id'] : 'NULL';
    $menu_name     = mysqli_real_escape_string($conn, $_POST['menu_name']);
    $menu_link     = mysqli_real_escape_string($conn, $_POST['menu_link']);
    $menu_icon     = mysqli_real_escape_string($conn, $_POST['menu_icon']);
    $allowed_roles = mysqli_real_escape_string($conn, $_POST['allowed_roles']);
    $is_grouped    = isset($_POST['is_grouped']) ? 1 : 0;
    $sort_order    = (int) $_POST['sort_order'];

    mysqli_query($conn, "
        INSERT INTO sidebar_menus
        (
            group_id,
            menu_name,
            menu_link,
            menu_icon,
            allowed_roles,
            is_grouped,
            sort_order
        )
        VALUES
        (
            $group_id,
            '$menu_name',
            '$menu_link',
            '$menu_icon',
            '$allowed_roles',
            '$is_grouped',
            '$sort_order'
        )
    ");

    header("Location: ./?link=sidebar_menu_manager.php&message=Menu+added+successfully");
    exit;
}

/*
|--------------------------------------------------------------------------
| FETCH DATA
|--------------------------------------------------------------------------
*/
$groups = mysqli_query($conn, "
    SELECT *
    FROM sidebar_groups
    ORDER BY sort_order ASC
");

$groupTable = mysqli_query($conn, "
    SELECT *
    FROM sidebar_groups
    ORDER BY sort_order ASC
");

$menuTable = mysqli_query($conn, "
    SELECT sm.*, sg.group_name
    FROM sidebar_menus sm
    LEFT JOIN sidebar_groups sg
    ON sm.group_id = sg.id
    ORDER BY sm.sort_order ASC
");
?>

<div class="container-fluid">

    <?php if (isset($_GET['message'])): ?>

        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">

            <i class="bi bi-check-circle-fill me-2"></i>

            <?= htmlspecialchars($_GET['message']) ?>

        </div>

    <?php endif; ?>
    <link rel="stylesheet" href="assets/css/nav-sidebar.css">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">

        <h2 class="page-title mb-0">
            <i class="bi bi-list"></i>
            Sidebar Menu Manager
        </h2>

        <div class="search-box">

            <i class="bi bi-search"></i>

            <input type="text"
                id="menuSearch"
                class="form-control"
                placeholder="Search menus...">

        </div>

    </div>

    <div class="row">

        <!-- ADD GROUP -->
        <div class="col-lg-4 mb-4">

            <div class="custom-card">

                <div class="card-body">

                    <h4>Add Menu Group</h4>

                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">
                                Group Name
                            </label>

                            <input type="text"
                                name="group_name"
                                class="form-control"
                                required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Bootstrap Icon
                            </label>

                            <input type="text"
                                name="group_icon"
                                class="form-control"
                                placeholder="bi bi-tools"
                                required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Group Key
                            </label>

                            <input type="text"
                                name="group_key"
                                class="form-control"
                                placeholder="engMenu"
                                required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Sort Order
                            </label>

                            <input type="number"
                                name="sort_order"
                                class="form-control"
                                value="1">

                        </div>

                        <button type="submit"
                            name="add_group"
                            class="btn btn-warning w-100">

                            Add Group

                        </button>

                    </form>

                </div>

            </div>

        </div>

        <!-- ADD MENU -->
        <div class="col-lg-8 mb-4">

            <div class="custom-card">

                <div class="card-body">

                    <h4>Add Menu</h4>

                    <form method="POST">

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Menu Name
                                </label>

                                <input type="text"
                                    name="menu_name"
                                    class="form-control"
                                    required>

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Menu Link
                                </label>

                                <input type="text"
                                    name="menu_link"
                                    class="form-control"
                                    placeholder="dashboard.php"
                                    required>

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Bootstrap Icon
                                </label>

                                <input type="text"
                                    name="menu_icon"
                                    class="form-control"
                                    placeholder="bi bi-grid"
                                    required>

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Group
                                </label>

                                <select name="group_id"
                                    class="form-select">

                                    <option value="">
                                        No Group
                                    </option>

                                    <?php while ($group = mysqli_fetch_assoc($groups)): ?>

                                        <option value="<?= $group['id'] ?>">
                                            <?= htmlspecialchars($group['group_name']) ?>
                                        </option>

                                    <?php endwhile; ?>

                                </select>

                            </div>

                            <div class="col-md-12 mb-3">

                                <label class="form-label">
                                    Allowed Roles
                                </label>

                                <input type="text"
                                    name="allowed_roles"
                                    class="form-control"
                                    placeholder="system_admin,management,engineer"
                                    required>

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Sort Order
                                </label>

                                <input type="number"
                                    name="sort_order"
                                    class="form-control"
                                    value="1">

                            </div>

                            <div class="col-md-6 mb-3 d-flex align-items-center">

                                <div class="form-check mt-4">

                                    <input class="form-check-input"
                                        type="checkbox"
                                        name="is_grouped"
                                        checked>

                                    <label class="form-check-label">

                                        Grouped Menu

                                    </label>

                                </div>

                            </div>

                        </div>

                        <button type="submit"
                            name="add_menu"
                            class="btn btn-warning w-100">

                            Add Menu

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

    <!-- MENU GROUP TABLE -->
    <div class="custom-card mt-4 mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h4 class="mb-0">
                    Menu Groups
                </h4>

                <span class="badge bg-primary rounded-pill px-3 py-2">
                    <?= mysqli_num_rows($groupTable) ?> Groups
                </span>

            </div>

            <div class="table-wrapper">

                <table class="table custom-table align-middle">

                    <thead>

                        <tr>

                            <th width="70">#</th>
                            <th>Group Name</th>
                            <th width="180">Key</th>
                            <th width="180">Icon</th>
                            <th width="120">Sort</th>
                            <th width="140">Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php while ($group = mysqli_fetch_assoc($groupTable)): ?>

                            <tr class="menu-row">

                                <td>

                                    <div class="table-id">
                                        #<?= $group['id'] ?>
                                    </div>

                                </td>

                                <td>

                                    <div class="fw-semibold">
                                        <?= htmlspecialchars($group['group_name']) ?>
                                    </div>

                                </td>

                                <td>

                                    <span class="menu-link-badge">
                                        <?= htmlspecialchars($group['group_key']) ?>
                                    </span>

                                </td>

                                <td>

                                    <div class="d-flex align-items-center gap-2">

                                        <div class="icon-box">

                                            <i class="<?= $group['group_icon'] ?>"></i>

                                        </div>

                                        <small class="text-secondary">
                                            <?= htmlspecialchars($group['group_icon']) ?>
                                        </small>

                                    </div>

                                </td>

                                <td>

                                    <span class="badge-soft-dark">
                                        <?= $group['sort_order'] ?>
                                    </span>

                                </td>

                                <td>

                                    <div class="d-flex gap-2">

                                        <!-- EDIT -->
                                        <button class="btn-action btn-edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editGroupModal<?= $group['id'] ?>">

                                            <i class="bi bi-pencil-square"></i>

                                        </button>

                                        <!-- DELETE -->
                                        <a href="./?link=sidebar_menu_manager.php&delete_group=<?= $group['id'] ?>"
                                            class="btn-action btn-delete text-decoration-none"
                                            onclick="return confirm('Delete this group?')">

                                            <i class="bi bi-trash"></i>

                                        </a>

                                    </div>

                                </td>

                            </tr>

                            <!-- EDIT GROUP MODAL -->
                            <div class="modal fade"
                                id="editGroupModal<?= $group['id'] ?>"
                                tabindex="-1">

                                <div class="modal-dialog modal-lg modal-dialog-centered">

                                    <div class="modal-content border-0 shadow-lg">

                                        <div class="modal-header border-0 pb-0">

                                            <h5 class="modal-title fw-bold">
                                                Edit Menu Group
                                            </h5>

                                            <button type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"></button>

                                        </div>

                                        <form method="POST">

                                            <div class="modal-body pt-3">

                                                <input type="hidden"
                                                    name="group_id"
                                                    value="<?= $group['id'] ?>">

                                                <div class="row">

                                                    <!-- GROUP NAME -->
                                                    <div class="col-md-6 mb-3">

                                                        <label class="form-label">
                                                            Group Name
                                                        </label>

                                                        <input type="text"
                                                            name="group_name"
                                                            class="form-control"
                                                            value="<?= htmlspecialchars($group['group_name']) ?>"
                                                            required>

                                                    </div>

                                                    <!-- GROUP KEY -->
                                                    <div class="col-md-6 mb-3">

                                                        <label class="form-label">
                                                            Group Key
                                                        </label>

                                                        <input type="text"
                                                            name="group_key"
                                                            class="form-control"
                                                            value="<?= htmlspecialchars($group['group_key']) ?>"
                                                            required>

                                                    </div>

                                                    <!-- ICON -->
                                                    <div class="col-md-6 mb-3">

                                                        <label class="form-label">
                                                            Bootstrap Icon
                                                        </label>

                                                        <input type="text"
                                                            name="group_icon"
                                                            class="form-control"
                                                            value="<?= htmlspecialchars($group['group_icon']) ?>"
                                                            required>

                                                    </div>

                                                    <!-- SORT -->
                                                    <div class="col-md-6 mb-3">

                                                        <label class="form-label">
                                                            Sort Order
                                                        </label>

                                                        <input type="number"
                                                            name="sort_order"
                                                            class="form-control"
                                                            value="<?= $group['sort_order'] ?>">

                                                    </div>

                                                </div>

                                            </div>

                                            <div class="modal-footer border-0 pt-0">

                                                <button type="button"
                                                    class="btn btn-light rounded-pill px-4"
                                                    data-bs-dismiss="modal">

                                                    Cancel

                                                </button>

                                                <button type="submit"
                                                    name="update_group"
                                                    class="btn btn-warning rounded-pill px-4">

                                                    Save Changes

                                                </button>

                                            </div>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- MENU TABLE -->
    <div class="custom-card">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h4 class="mb-0">
                    Existing Menus
                </h4>

                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                    <?= mysqli_num_rows($menuTable) ?> Menus
                </span>

            </div>

            <div class="table-wrapper">

                <table class="table custom-table align-middle">

                    <thead>

                        <tr>

                            <th width="70">#</th>
                            <th>Menu</th>
                            <th width="180">Link</th>
                            <th width="170">Icon</th>
                            <th>Group</th>
                            <th>Roles</th>
                            <th width="160">Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php while ($menu = mysqli_fetch_assoc($menuTable)): ?>

                            <tr class="menu-row">

                                <td>

                                    <div class="table-id">
                                        #<?= $menu['id'] ?>
                                    </div>

                                </td>

                                <td>

                                    <div class="fw-semibold fs-6">
                                        <?= htmlspecialchars($menu['menu_name']) ?>
                                    </div>

                                </td>

                                <td>

                                    <span class="menu-link-badge">
                                        <?= htmlspecialchars($menu['menu_link']) ?>
                                    </span>

                                </td>

                                <td>

                                    <div class="d-flex align-items-center gap-2">

                                        <div class="icon-box">

                                            <i class="<?= $menu['menu_icon'] ?>"></i>

                                        </div>

                                        <small class="text-secondary">
                                            <?= htmlspecialchars($menu['menu_icon']) ?>
                                        </small>

                                    </div>

                                </td>

                                <td>

                                    <?php if ($menu['group_name']): ?>

                                        <span class="badge-soft">

                                            <?= htmlspecialchars($menu['group_name']) ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="badge-soft-dark">
                                            No Group
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <div class="role-tags">

                                        <?php
                                        $roles = explode(',', $menu['allowed_roles']);

                                        foreach ($roles as $role):
                                        ?>

                                            <span class="role-badge">

                                                <?= trim($role) ?>

                                            </span>

                                        <?php endforeach; ?>

                                    </div>

                                </td>

                                <td>

                                    <div class="d-flex gap-2">

                                        <!-- EDIT -->
                                        <button class="btn-action btn-edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal<?= $menu['id'] ?>">

                                            <i class="bi bi-pencil-square"></i>

                                        </button>

                                        <!-- DELETE -->
                                        <a href="./?link=sidebar_menu_manager.php&delete=<?= $menu['id'] ?>"
                                            class="btn-action btn-delete text-decoration-none"
                                            onclick="return confirm('Delete this menu?')">

                                            <i class="bi bi-trash"></i>

                                        </a>

                                    </div>

                                </td>

                            </tr>

                            <!-- EDIT MODAL -->
                            <div class="modal fade"
                                id="editModal<?= $menu['id'] ?>"
                                tabindex="-1">

                                <div class="modal-dialog modal-lg modal-dialog-centered">

                                    <div class="modal-content border-0 shadow-lg">

                                        <div class="modal-header border-0 pb-0">

                                            <h5 class="modal-title fw-bold">
                                                Edit Menu
                                            </h5>

                                            <button type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"></button>

                                        </div>

                                        <form method="POST">

                                            <div class="modal-body pt-3">

                                                <input type="hidden"
                                                    name="menu_id"
                                                    value="<?= $menu['id'] ?>">

                                                <div class="row">

                                                    <!-- MENU NAME -->
                                                    <div class="col-md-6 mb-3">

                                                        <label class="form-label">
                                                            Menu Name
                                                        </label>

                                                        <input type="text"
                                                            name="menu_name"
                                                            class="form-control"
                                                            value="<?= htmlspecialchars($menu['menu_name']) ?>"
                                                            required>

                                                    </div>

                                                    <!-- MENU LINK -->
                                                    <div class="col-md-6 mb-3">

                                                        <label class="form-label">
                                                            Menu Link
                                                        </label>

                                                        <input type="text"
                                                            name="menu_link"
                                                            class="form-control"
                                                            value="<?= htmlspecialchars($menu['menu_link']) ?>"
                                                            required>

                                                    </div>

                                                    <!-- ICON -->
                                                    <div class="col-md-6 mb-3">

                                                        <label class="form-label">
                                                            Bootstrap Icon
                                                        </label>

                                                        <input type="text"
                                                            name="menu_icon"
                                                            class="form-control"
                                                            value="<?= htmlspecialchars($menu['menu_icon']) ?>"
                                                            required>

                                                    </div>

                                                    <!-- GROUP -->
                                                    <div class="col-md-6 mb-3">

                                                        <label class="form-label">
                                                            Group
                                                        </label>

                                                        <select name="group_id"
                                                            class="form-select">

                                                            <option value="">
                                                                No Group
                                                            </option>

                                                            <?php
                                                            $modalGroups = mysqli_query($conn, "
                                                            SELECT *
                                                            FROM sidebar_groups
                                                            ORDER BY sort_order ASC
                                                        ");

                                                            while ($groupOption = mysqli_fetch_assoc($modalGroups)):
                                                            ?>

                                                                <option value="<?= $groupOption['id'] ?>"
                                                                    <?= ($menu['group_id'] == $groupOption['id']) ? 'selected' : '' ?>>

                                                                    <?= htmlspecialchars($groupOption['group_name']) ?>

                                                                </option>

                                                            <?php endwhile; ?>

                                                        </select>

                                                    </div>

                                                    <!-- ROLES -->
                                                    <div class="col-md-12 mb-3">

                                                        <label class="form-label">
                                                            Allowed Roles
                                                        </label>

                                                        <input type="text"
                                                            name="allowed_roles"
                                                            class="form-control"
                                                            value="<?= htmlspecialchars($menu['allowed_roles']) ?>"
                                                            required>

                                                    </div>

                                                    <!-- SORT -->
                                                    <div class="col-md-12 mb-3">

                                                        <label class="form-label">
                                                            Sort Order
                                                        </label>

                                                        <input type="number"
                                                            name="sort_order"
                                                            class="form-control"
                                                            value="<?= $menu['sort_order'] ?>">

                                                    </div>

                                                </div>

                                            </div>

                                            <div class="modal-footer border-0 pt-0">

                                                <button type="button"
                                                    class="btn btn-light rounded-pill px-4"
                                                    data-bs-dismiss="modal">

                                                    Cancel

                                                </button>

                                                <button type="submit"
                                                    name="update_menu"
                                                    class="btn btn-warning rounded-pill px-4">

                                                    Save Changes

                                                </button>

                                            </div>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<script>
    const searchInput = document.getElementById('menuSearch');

    if (searchInput) {

        searchInput.addEventListener('keyup', function() {

            let value = this.value.toLowerCase();

            document.querySelectorAll('.menu-row').forEach(row => {

                let text = row.innerText.toLowerCase();

                row.style.display = text.includes(value) ?
                    '' :
                    'none';

            });

        });

    }
</script>