<?php
ob_start();

include 'auth.php';
include 'database.php';


$role = $_SESSION['role'] ?? '';
$isManagement = isset($_SESSION['role']) && $_SESSION['role'] === 'management';
if($isManagement){

    if(
        isset($_POST['save']) ||
        isset($_POST['update']) ||
        isset($_POST['submit_request']) ||
        isset($_GET['delete'])
    ){
        die("
            <div style='padding:30px;font-family:Arial'>
                <h2>Access Denied</h2>
                <p>Management role is view only.</p>
            </div>
        ");
    }
}
if($role === 'ceo'){
    $link = $_GET['link'] ?? 'dashboard.php';
}else{
    $link = $_GET['link'] ?? 'overview.php';
}


/*
|--------------------------------------------------------------------------
| GET USER ROLE
|--------------------------------------------------------------------------
*/
$role = $_SESSION['role'] ?? 'engineer';

/*
|--------------------------------------------------------------------------
| GET ALLOWED MENUS FROM DATABASE
|--------------------------------------------------------------------------
*/

$allowed = [];

$query = mysqli_query($conn, "
    SELECT menu_link, allowed_roles
    FROM sidebar_menus
");

while ($row = mysqli_fetch_assoc($query)) {

    $roles = array_map('trim', explode(',', $row['allowed_roles']));

    if (
        $role === 'super_admin' ||
        in_array($role, $roles)
    ) {

        $allowed[] = $row['menu_link'];
    }
}

/*
|--------------------------------------------------------------------------
| STATIC PAGES
|--------------------------------------------------------------------------
*/

$staticPages = [

    'contractor_profile.php'

];

$allowed = array_merge($allowed, $staticPages);

/*
|--------------------------------------------------------------------------
| DEFAULT PAGES
|--------------------------------------------------------------------------
*/

$allowed[] = 'coming_soon.php';

/*
|--------------------------------------------------------------------------
| ROUTE VALIDATION
|--------------------------------------------------------------------------
*/

if (!in_array($link, $allowed)) {

    $link = 'coming_soon.php';
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BuildTrack</title>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">

    <style>
        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid #ced4da;
            padding-top: 4px;
        }

        .select2-selection__rendered {
            color: #111 !important;
            line-height: 30px !important;
        }

        .select2-dropdown {
            z-index: 9999 !important;
            color: #111;
        }

        .select2-results__option {
            color: #111 !important;
        }

        .select2-search__field {
            color: #111 !important;
        }

        /* 
        .table td,
        .table th {
            background: transparent !important;
            color: #FFF !important;
        } */

        canvas {
            width: 100% !important;
            height: 320px !important;
        }

        .text-num {
            text-align: right !important;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>

<body>

    <!-- MOBILE NAVBAR -->
    <nav class="navbar navbar-dark bg-dark d-lg-none px-3">
        <a class="navbar-brand fw-bold" href="./?link=dashboard.php">
            <i class="bi bi-buildings text-warning"></i> BuildTrack
        </a>

        <button class="navbar-toggler"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#sidebarMobile">
            <span class="navbar-toggler-icon"></span>
        </button>
    </nav>

    <div class="container-fluid">
        <div class="row g-0">

            <!-- DESKTOP SIDEBAR -->
            <aside class="col-lg-2 d-none d-lg-block sidebar p-3">
                <?php include 'nav-sidebar.php'; ?>
            </aside>

            <!-- MAIN CONTENT -->
            <main class="col-12 col-lg-10 p-3 p-lg-4">

                <?php
                if (file_exists($link)) {
                    include $link;
                } else {
                    include 'coming_soon.php';
                }
                ?>

            </main>

        </div>
    </div>

    <!-- MOBILE SIDEBAR -->
    <div class="offcanvas offcanvas-start bg-dark text-white"
        tabindex="-1"
        id="sidebarMobile">

        <div class="offcanvas-header">
            <h5 class="offcanvas-title">
                <i class="bi bi-buildings text-warning"></i> BuildTrack
            </h5>

            <button type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="offcanvas">
            </button>
        </div>

        <div class="offcanvas-body sidebar p-3">
            <?php include 'nav-sidebar.php'; ?>
        </div>
    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(function() {

            function initSelect2(context = document) {

                $(context).find('select').each(function() {

                    const $el = $(this);

                    if ($el.hasClass('no-select2')) return;

                    // destroy old instance first
                    if ($el.hasClass('select2-hidden-accessible')) {
                        $el.select2('destroy');
                    }

                    let parent = $('body');

                    if ($el.closest('.modal').length) {
                        parent = $el.closest('.modal');
                    }

                    $el.select2({
                        width: '100%',
                        dropdownParent: parent,
                        placeholder: $el.attr('data-placeholder') || 'Select option',
                        allowClear: false
                    });

                });
            }

            // first load
            initSelect2();

            // when modal opens
            $(document).on('shown.bs.modal', '.modal', function() {
                initSelect2(this);
            });

        });
        function toggleFullscreen(btn){

    let card = btn.closest('.card');

    card.classList.toggle('fullscreen-card');

    document.body.classList.toggle('sidebar-hidden');

    let icon = btn.querySelector('i');

    if(card.classList.contains('fullscreen-card')){
        icon.className = 'bi bi-fullscreen-exit';
    }else{
        icon.className = 'bi bi-arrows-fullscreen';
    }
}
    </script>
</body>

</html>
<?php ob_end_flush(); ?>