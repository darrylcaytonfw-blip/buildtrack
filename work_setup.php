<?php
include 'database.php';

/* =====================================================
   WORK SETUP - PART 1
   CORE CRUD + DATA + STYLES
===================================================== */

/* =========================
   CATEGORY
========================= */
if (isset($_POST['save_category'])) {
    $name = trim($_POST['category_name']);
    $stmt = $conn->prepare("INSERT INTO work_categories(category_name) VALUES(?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    header("Location: ./?link=work_setup.php");
    exit;
}

if (isset($_POST['update_category'])) {
    $id   = (int)$_POST['id'];
    $name = trim($_POST['category_name']);

    $stmt = $conn->prepare("UPDATE work_categories SET category_name=? WHERE id=?");
    $stmt->bind_param("si", $name, $id);
    $stmt->execute();

    header("Location: ./?link=work_setup.php");
    exit;
}

/* =========================
   DISCIPLINE
========================= */
if (isset($_POST['save_discipline'])) {
    $cat  = (int)$_POST['category_id'];
    $name = trim($_POST['discipline_name']);

    $stmt = $conn->prepare("INSERT INTO work_disciplines(category_id,discipline_name) VALUES(?,?)");
    $stmt->bind_param("is", $cat, $name);
    $stmt->execute();

    header("Location: ./?link=work_setup.php");
    exit;
}

if (isset($_POST['update_discipline'])) {
    $id   = (int)$_POST['id'];
    $cat  = (int)$_POST['category_id'];
    $name = trim($_POST['discipline_name']);

    $stmt = $conn->prepare("
        UPDATE work_disciplines
        SET category_id=?, discipline_name=?
        WHERE id=?
    ");
    $stmt->bind_param("isi", $cat, $name, $id);
    $stmt->execute();

    header("Location: ./?link=work_setup.php");
    exit;
}

/* =========================
   ACTIVITY
========================= */
if (isset($_POST['save_activity'])) {
    $cat  = (int)$_POST['category_id'];
    $dis  = (int)$_POST['discipline_id'];
    $name = trim($_POST['activity_name']);

    $stmt = $conn->prepare("
        INSERT INTO work_activities(category_id,discipline_id,activity_name)
        VALUES(?,?,?)
    ");
    $stmt->bind_param("iis", $cat, $dis, $name);
    $stmt->execute();

    header("Location: ./?link=work_setup.php");
    exit;
}

if (isset($_POST['update_activity'])) {
    $id   = (int)$_POST['id'];
    $cat  = (int)$_POST['category_id'];
    $dis  = (int)$_POST['discipline_id'];
    $name = trim($_POST['activity_name']);

    $stmt = $conn->prepare("
        UPDATE work_activities
        SET category_id=?, discipline_id=?, activity_name=?
        WHERE id=?
    ");
    $stmt->bind_param("iisi", $cat, $dis, $name, $id);
    $stmt->execute();

    header("Location: ./?link=work_setup.php");
    exit;
}

/* =========================
   ITEM
========================= */
if (isset($_POST['save_item'])) {

    $activity_id = (int)$_POST['activity_id'];
    $item_name   = trim($_POST['item_name']);
    $unit        = trim($_POST['unit']);
    $est         = (float)$_POST['estimated_price'];
    $act         = (float)$_POST['actual_price'];

    $meta = $conn->query("
        SELECT wc.id cat_id, wd.id dis_id, wa.id act_id
        FROM work_activities wa
        LEFT JOIN work_disciplines wd ON wa.discipline_id = wd.id
        LEFT JOIN work_categories wc ON wd.category_id = wc.id
        WHERE wa.id = $activity_id
        LIMIT 1
    ")->fetch_assoc();

    $cat  = $meta['cat_id'] ?? 1;
    $dis  = $meta['dis_id'] ?? 1;
    $actx = $meta['act_id'] ?? 1;

    $cnt  = $conn->query("SELECT COUNT(*) c FROM work_items WHERE activity_id=$activity_id")->fetch_assoc();
    $next = ((int)$cnt['c']) + 1;

    $item_no = $cat . '.' . $dis . '.' . $actx . '.' . $next;

    $stmt = $conn->prepare("
        INSERT INTO work_items(activity_id,item_no,item_name,unit,estimated_price,actual_price)
        VALUES(?,?,?,?,?,?)
    ");
    $stmt->bind_param("isssdd", $activity_id, $item_no, $item_name, $unit, $est, $act);
    $stmt->execute();

    header("Location: ./?link=work_setup.php");
    exit;
}

if (isset($_POST['update_item'])) {

    $id          = (int)$_POST['id'];
    $activity_id = (int)$_POST['activity_id'];
    $item_no     = trim($_POST['item_no']);
    $item_name   = trim($_POST['item_name']);
    $unit        = trim($_POST['unit']);
    $est         = (float)$_POST['estimated_price'];
    $act         = (float)$_POST['actual_price'];

    $stmt = $conn->prepare("
        UPDATE work_items
        SET activity_id=?,
            item_no=?,
            item_name=?,
            unit=?,
            estimated_price=?,
            actual_price=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "isssddi",
        $activity_id,
        $item_no,
        $item_name,
        $unit,
        $est,
        $act,
        $id
    );
    $stmt->execute();

    header("Location: ./?link=work_setup.php");
    exit;
}

/* =========================
   DELETE
========================= */
if (isset($_GET['delcat'])) {
    $id = (int)$_GET['delcat'];

    $acts = $conn->query("
        SELECT wa.id
        FROM work_activities wa
        LEFT JOIN work_disciplines wd ON wa.discipline_id=wd.id
        WHERE wd.category_id=$id
    ");

    while ($a = $acts->fetch_assoc()) {
        $conn->query("DELETE FROM work_items WHERE activity_id=" . $a['id']);
    }

    $conn->query("
        DELETE wa FROM work_activities wa
        LEFT JOIN work_disciplines wd ON wa.discipline_id=wd.id
        WHERE wd.category_id=$id
    ");

    $conn->query("DELETE FROM work_disciplines WHERE category_id=$id");
    $conn->query("DELETE FROM work_categories WHERE id=$id");

    header("Location: ./?link=work_setup.php");
    exit;
}

if (isset($_GET['deldis'])) {
    $id = (int)$_GET['deldis'];

    $acts = $conn->query("SELECT id FROM work_activities WHERE discipline_id=$id");
    while ($a = $acts->fetch_assoc()) {
        $conn->query("DELETE FROM work_items WHERE activity_id=" . $a['id']);
    }

    $conn->query("DELETE FROM work_activities WHERE discipline_id=$id");
    $conn->query("DELETE FROM work_disciplines WHERE id=$id");

    header("Location: ./?link=work_setup.php");
    exit;
}

if (isset($_GET['delact'])) {
    $id = (int)$_GET['delact'];

    $conn->query("DELETE FROM work_items WHERE activity_id=$id");
    $conn->query("DELETE FROM work_activities WHERE id=$id");

    header("Location: ./?link=work_setup.php");
    exit;
}

if (isset($_GET['delitem'])) {
    $id = (int)$_GET['delitem'];

    $conn->query("DELETE FROM work_items WHERE id=$id");

    header("Location: ./?link=work_setup.php");
    exit;
}

/* =========================
   DATA
========================= */
$activities = $conn->query("
SELECT wa.*, wc.category_name, wd.discipline_name
FROM work_activities wa
LEFT JOIN work_categories wc ON wa.category_id=wc.id
LEFT JOIN work_disciplines wd ON wa.discipline_id=wd.id
ORDER BY wc.category_name, wd.discipline_name, wa.activity_name
");

$items = $conn->query("
SELECT wi.*, wa.activity_name, wa.category_id, wa.discipline_id,
       wd.discipline_name, wc.category_name
FROM work_items wi
LEFT JOIN work_activities wa ON wi.activity_id=wa.id
LEFT JOIN work_disciplines wd ON wa.discipline_id=wd.id
LEFT JOIN work_categories wc ON wd.category_id=wc.id
ORDER BY wc.category_name, wd.discipline_name, wi.item_no
");
?>

<style>
    .card {
        background: rgba(255, 255, 255, .05) !important;
        border: 1px solid rgba(212, 175, 55, .15) !important;
        border-radius: 20px !important;
        box-shadow: 0 15px 35px rgba(0, 0, 0, .25);
        color: #fff;
    }

    .table-modern {
        width: 100%;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0 8px;
    }

    tr td,
    th {
        background: transparent !important;
        color: #FFF !important;
    }

    .table-modern thead th,
    .table-modern tbody td {
        padding: 12px 14px;
        border: none;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .table-modern tbody tr {
        background: rgba(255, 255, 255, .04);
    }

    .table-modern tbody tr:hover {
        background: rgba(212, 175, 55, .08);
    }

    .col-cat {
        width: 180px;
    }

    .col-dis {
        width: 180px;
    }

    .col-act {
        width: 220px;
    }

    .col-item {
        width: 260px;
    }

    .col-no {
        width: 120px;
    }

    .col-unit {
        width: 90px;
    }

    .col-money {
        width: 160px;
    }

    .col-action {
        width: 190px;
    }

    .text-num {
        text-align: right !important;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .btn-edit {
        background: #0d6efd;
        border: none;
        color: #fff;
    }

    .btn-del {
        background: #003072;
        border: none;
        color: #fff;
    }

</style>
<div class="row g-4">
    <div class="col-lg-4"><!-- LEFT PANEL FORMS -->

        <div class="card p-4 mb-4">
            <h5>Add Discipline</h5>
            <form method="post">
                <input name="category_name" class="form-control mb-3" required>
                <button name="save_category" class="btn btn-gold w-100">Save</button>
            </form>
        </div>

        <div class="card p-4 mb-4">
            <h5>Add Trade Package</h5>
            <form method="post">
                <select name="category_id" class="form-select mb-3" required>
                    <option value="">Select Category</option>
                    <?php
                    $cq = $conn->query("SELECT * FROM work_categories ORDER BY category_name");
                    while ($c = $cq->fetch_assoc()):
                    ?>
                        <option value="<?= $c['id'] ?>"><?= $c['category_name'] ?></option>
                    <?php endwhile; ?>
                </select>

                <input name="discipline_name" class="form-control my-3" required>
                <button name="save_discipline" class="btn btn-gold w-100">Save</button>
            </form>
        </div>

        <div class="card p-4 mb-4">
            <h5>Add Work Description</h5>
            <form method="post">

                <select name="category_id" class="form-select mb-3" required>
                    <option value="">Category</option>
                    <?php
                    $cq2 = $conn->query("SELECT * FROM work_categories ORDER BY category_name");
                    while ($c = $cq2->fetch_assoc()):
                    ?>
                        <option value="<?= $c['id'] ?>"><?= $c['category_name'] ?></option>
                    <?php endwhile; ?>
                </select>
<div class="mb-3"></div>
                <select name="discipline_id" class="form-select mb-3" required>
                    <option value="">Discipline</option>
                    <?php
                    $dq = $conn->query("SELECT * FROM work_disciplines ORDER BY discipline_name");
                    while ($d = $dq->fetch_assoc()):
                    ?>
                        <option value="<?= $d['id'] ?>"><?= $d['discipline_name'] ?></option>
                    <?php endwhile; ?>
                </select>

                <input name="activity_name" class="form-control my-3" required>
                <button name="save_activity" class="btn btn-gold w-100">Save</button>
            </form>
        </div>

        <div class="card p-4 mb-4">
            <h5>Add Item</h5>
            <form method="post" class="row g-3">

                <div class="col-12">
                    <select name="activity_id" class="form-select" required>
                        <option value="">Select Activity</option>
                        <?php
                        $aq = $conn->query("
                SELECT wa.id, wa.activity_name, wd.discipline_name, wc.category_name
                FROM work_activities wa
                LEFT JOIN work_disciplines wd ON wa.discipline_id=wd.id
                LEFT JOIN work_categories wc ON wd.category_id=wc.id
                ORDER BY wc.category_name, wd.discipline_name, wa.activity_name
                ");
                        while ($a = $aq->fetch_assoc()):
                        ?>
                            <option value="<?= $a['id'] ?>">
                                <?= $a['category_name'] ?> /
                                <?= $a['discipline_name'] ?> /
                                <?= $a['activity_name'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-8">
                    <input type="text" name="item_name" class="form-control" placeholder="Item Name" required>
                </div>

                <div class="col-4">
                    <select name="unit" class="form-select">
                        <option value="sqm">sqm</option>
                        <option value="m3">m3</option>
                        <option value="pcs">pcs</option>
                        <option value="lot">lot</option>
                        <option value="set">set</option>
                        <option value="lm">lm</option>
                    </select>
                </div>

                <div class="col-6">
                    <input type="number" step="0.01" name="estimated_price" class="form-control" placeholder="Estimated Price">
                </div>

                <div class="col-6">
                    <input type="number" step="0.01" name="actual_price" class="form-control" placeholder="Actual Price">
                </div>

                <div class="col-12">
                    <button name="save_item" class="btn btn-gold w-100">Save Item</button>
                </div>

            </form>
        </div>

    </div>
    <div class="col-lg-8">

        <!-- CATEGORIES TABLE -->
        <div class="card p-3 mb-4">
            <h5 class="mb-3">Discipline</h5>

            <div class="table-responsive">
                <table class="table table-modern grid-table">

                    <colgroup>
                        <col style="width:30%">
                        <col style="width:30%">
                        <col style="width:25%">
                        <col style="width:15%">
                    </colgroup>

                    <thead>
                        <tr>
                            <th>Category</th>
                            <th></th>
                            <th></th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $catList = $conn->query("SELECT * FROM work_categories ORDER BY category_name");
                        while ($r = $catList->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= $r['category_name'] ?></td>
                                <td></td>
                                <td></td>
                                <td class="text-center">
                                    <a href="./?link=work_setup.php&delcat=<?= $r['id'] ?>"
                                        class="btn btn-sm btn-del">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>
            </div>
        </div>

        <!-- DISCIPLINES TABLE -->
        <div class="card p-3 mb-4">
            <h5 class="mb-3">Trade Package</h5>

            <div class="table-responsive">
                <table class="table table-modern grid-table">

                    <colgroup>
                        <col style="width:30%">
                        <col style="width:30%">
                        <col style="width:25%">
                        <col style="width:15%">
                    </colgroup>

                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Discipline</th>
                            <th></th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $dl = $conn->query("
                        SELECT wd.*, wc.category_name
                        FROM work_disciplines wd
                        LEFT JOIN work_categories wc ON wd.category_id = wc.id
                        ORDER BY wc.category_name, wd.discipline_name
                    ");
                        while ($r = $dl->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= $r['category_name'] ?></td>
                                <td><?= $r['discipline_name'] ?></td>
                                <td></td>
                                <td class="text-center">
                                    <a href="./?link=work_setup.php&deldis=<?= $r['id'] ?>"
                                        class="btn btn-sm btn-del">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>
            </div>
        </div>

        <!-- ACTIVITIES TABLE -->
        <div class="card p-3 mb-4">
            <h5 class="mb-3">Activities</h5>

            <div class="table-responsive">
                <table class="table table-modern grid-table">

                    <colgroup>
                        <col style="width:30%">
                        <col style="width:30%">
                        <col style="width:25%">
                        <col style="width:15%">
                    </colgroup>

                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Discipline</th>
                            <th>Activity</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($r = $activities->fetch_assoc()): ?>
                            <tr>
                                <td><?= $r['category_name'] ?></td>
                                <td><?= $r['discipline_name'] ?></td>
                                <td><?= $r['activity_name'] ?></td>
                                <td class="text-center">
                                    <a href="./?link=work_setup.php&delact=<?= $r['id'] ?>"
                                        class="btn btn-sm btn-del">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>
            </div>
        </div>

        <!-- ITEMS TABLE FIXED -->
        <div class="card p-3 mb-4">
            <h5 class="mb-3">Items</h5>

            <div class="table-responsive">
                <table class="table table-modern items-table">

                    <colgroup>
                        <col style="width:168.31px">
                        <col style="width:168.31px">
                        <col style="width:140.26px">
                        <col style="width:140px">
                        <col style="width:140px">
                        <col style="width:140px">
                        <col style="width:84.18px">
                    </colgroup>

                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Discipline</th>
                            <th>Activity</th>
                            <th class="text-end">No</th>
                            <th>Item</th>
                            <th class="text-center">Unit</th>
                            <th class="text-center position-sticky end-0">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($r = $items->fetch_assoc()): ?>
                            <tr>
                                <td><?= $r['category_name'] ?></td>
                                <td><?= $r['discipline_name'] ?></td>
                                <td><?= $r['activity_name'] ?></td>
                                <td class="text-end"><?= $r['item_no'] ?></td>
                                <td><?= $r['item_name'] ?></td>
                                <td class="text-center"><?= strtoupper($r['unit']) ?></td>
                                <td class="text-end position-sticky end-0">
                                    <a href="./?link=work_setup.php&delitem=<?= $r['id'] ?>"
                                        class="btn btn-sm btn-del">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>
            </div>
        </div>

    </div>
</div>