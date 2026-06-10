<?php
$current = $_GET['link'] ?? 'overview.php' ?? 'dashboard.php';
$role    = $_SESSION['role'] ?? 'engineer';

/*
|--------------------------------------------------------------------------
| REMOVE URL HASH (#section)
|--------------------------------------------------------------------------
*/

$currentClean = explode('#', $current)[0];

if (!function_exists('active')) {

    function active($page, $current)
    {
        $page    = explode('#', $page)[0];
        $current = explode('#', $current)[0];

        return $page == $current ? 'active' : '';
    }
}

$roleLabels = [
    'management'       => 'Management',
    'owner'            => 'Owner',
    'ceo'              => 'CEO',
    'project_manager'  => 'Project Manager',
    'finance'          => 'Finance',
    'engineer'         => 'Engineer',
    'supplier'         => 'Supplier / Contractor',
    'system_admin'     => 'System Admin',
    'contractor_staff' => 'Contractor Staff'
];

$roleName = $roleLabels[$role] ?? strtoupper($role);

/*
|--------------------------------------------------------------------------
| GET ALL GROUP MENUS FOR AUTO OPEN
|--------------------------------------------------------------------------
*/

$openGroups = [];

$groupPagesQuery = mysqli_query($conn, "
    SELECT sg.group_key, sm.menu_link
    FROM sidebar_groups sg
    INNER JOIN sidebar_menus sm ON sg.id = sm.group_id
");

while ($gp = mysqli_fetch_assoc($groupPagesQuery)) {

    $menuLinkClean = explode('#', $gp['menu_link'])[0];

    if ($menuLinkClean == $currentClean) {
        $openGroups[] = $gp['group_key'];
    }
}
?>

<style>
    .sidebar a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 12px;
        color: #cbd5e1;
        text-decoration: none;
        margin-bottom: 4px;
        transition: .2s ease;
    }

    .sidebar a:hover {
        background: rgba(255, 255, 255, .06);
        color: #fff;
    }

    .sidebar a.active {
        background: rgba(255, 255, 255, .08);
        color: #fff;
    }

    .menu-title {
        color: #94a3b8;
        font-size: .75rem;
        text-transform: uppercase;
        padding: 12px 8px 6px;
        letter-spacing: .08em;
    }

    .group-toggle {
        width: 100%;
        border: 0;
        background: transparent;
        color: #fff;
        text-align: left;
        padding: 10px 14px;
        border-radius: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .group-toggle:hover {
        background: rgba(255, 255, 255, .06);
    }

    .submenu {
        padding-left: 10px;
        margin-top: 4px;
    }

    .user-panel {
        background: rgba(255, 255, 255, .04) !important;
        border-radius: 18px;
    }
</style>

<!-- LOGO -->
<div class="text-center mb-4">
    <img src="assets/images/logo.png"
        class="img-fluid"
        style="max-width:140px;">
</div>

<?php
/*
|--------------------------------------------------------------------------
| NON GROUPED MENUS
|--------------------------------------------------------------------------
*/

$commonMenus = mysqli_query($conn, "
    SELECT *
    FROM sidebar_menus
    WHERE is_grouped = 0
    ORDER BY sort_order ASC
");

while ($menu = mysqli_fetch_assoc($commonMenus)) {

    $roles = array_map('trim', explode(',', $menu['allowed_roles']));

    if (in_array($role, $roles)) :
?>

        <a class="<?= active($menu['menu_link'], $current) ?>"
            href="./?link=<?= $menu['menu_link'] ?>">

            <i class="<?= $menu['menu_icon'] ?>"></i>
            <?= htmlspecialchars($menu['menu_name']) ?>

        </a>

<?php
    endif;
}
?>

<?php
/*
|--------------------------------------------------------------------------
| GROUP MENUS
|--------------------------------------------------------------------------
*/

$groups = mysqli_query($conn, "
    SELECT *
    FROM sidebar_groups
    ORDER BY sort_order ASC
");

while ($group = mysqli_fetch_assoc($groups)) :

    $groupID  = $group['id'];
    $groupKey = $group['group_key'];

    $menusQuery = mysqli_query($conn, "
        SELECT *
        FROM sidebar_menus
        WHERE group_id = '$groupID'
        ORDER BY sort_order ASC
    ");

    $menus = [];
    $hasAccess = false;

    while ($menu = mysqli_fetch_assoc($menusQuery)) {

        $roles = array_map('trim', explode(',', $menu['allowed_roles']));

        if (in_array($role, $roles)) {
            $menus[] = $menu;
            $hasAccess = true;
        }
    }

    if (!$hasAccess) {
        continue;
    }

    $showClass = in_array($groupKey, $openGroups) ? 'show' : '';
?>

    <div class="menu-title">
        <?= htmlspecialchars($group['group_name']) ?>
    </div>

    <button class="group-toggle"
        data-bs-toggle="collapse"
        data-bs-target="#<?= $groupKey ?>">

        <span>
            <i class="<?= $group['group_icon'] ?> me-2"></i>
            <?= htmlspecialchars($group['group_name']) ?>
        </span>

        <i class="bi bi-chevron-down"></i>

    </button>

    <div class="collapse <?= $showClass ?> submenu"
        id="<?= $groupKey ?>">

        <?php foreach ($menus as $menu): ?>

            <a class="menu-link <?= active($menu['menu_link'], $current) ?>"
                data-link="<?= $menu['menu_link'] ?>"
                href="./?link=<?= $menu['menu_link'] ?>">

                <i class="<?= $menu['menu_icon'] ?>"></i>
                <?= htmlspecialchars($menu['menu_name']) ?>

            </a>

        <?php endforeach; ?>

    </div>

<?php endwhile; ?>

<hr class="border-secondary my-3">

<!-- USER PANEL -->
<div class="card p-3 mb-3 border-0 user-panel">

    <div class="text-center">

        <small class="text-secondary d-block">
            Logged in as
        </small>

        <div class="fs-1 text-white">
            <i class="bi bi-person-circle"></i>
        </div>

        <strong class="text-white d-block">
            <?= $_SESSION['user'] ?? 'Guest' ?>
        </strong>

        <span class="badge rounded-pill bg-warning text-dark mt-2 px-3 py-2">
            <?= $roleName ?>
        </span>

    </div>

</div>

<a href="logout.php">
    <i class="bi bi-box-arrow-right"></i>
    Logout
</a>
<script>
function updateActiveMenu(){

    const params = new URLSearchParams(window.location.search);

    const currentLink =
        (params.get('link') || '') +
        window.location.hash;

    document.querySelectorAll('.menu-link').forEach(link => {

        link.classList.remove('active');

        const menuLink = link.dataset.link;

        if(menuLink === currentLink){

            link.classList.add('active');
        }
    });
}

document.addEventListener('DOMContentLoaded', updateActiveMenu);

window.addEventListener('hashchange', updateActiveMenu);

document.querySelectorAll('.menu-link').forEach(link => {

    link.addEventListener('click', function(){

        setTimeout(updateActiveMenu, 10);
    });
});
</script>