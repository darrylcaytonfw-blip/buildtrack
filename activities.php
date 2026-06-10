<?php
include 'database.php';
/*
activities.php
3-Level Integrated Version
Project + Contractor + Category + Activity + Item
Source = work_setup master tables
*/
if (isset($_POST['save'])) {
    $project_id    = (int)$_POST['project_id'];
    $contractor_id = (int)$_POST['contractor_id'];
    $item_id       = (int)$_POST['item_id'];
    $target_qty    = (float)$_POST['target_qty'];
    if ($target_qty <= 0) $target_qty = 100;
    $it = $conn->query("
        SELECT wi.item_no,
               wi.item_name,
               wi.unit
        FROM work_items wi
        WHERE wi.id=$item_id
        LIMIT 1
    ")->fetch_assoc();
    if ($it) {
        $item_no     = $it['item_no'];
        $description = $it['item_name'];
        $progress    = 0;
        $stmt = $conn->prepare("
            INSERT INTO activities(
                project_id,
                contractor_id,
                item_id,
                item_no,
                description,
                progress,
                target_qty
            )
            VALUES(?,?,?,?,?,?,?)
        ");
        $stmt->bind_param(
            "iiissdd",
            $project_id,
            $contractor_id,
            $item_id,
            $item_no,
            $description,
            $progress,
            $target_qty
        );
        $stmt->execute();
    }
    header("Location: ./?link=activities.php&added=1");
    exit;
}
/* DELETE */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM work_updates WHERE activity_id=$id");
    $conn->query("DELETE FROM activities WHERE id=$id");
    header("Location: ./?link=activities.php&deleted=1");
    exit;
}
/* DATA */
$projects = $conn->query("
SELECT id, project_name
FROM projects
ORDER BY project_name
");
$contractors = $conn->query("
SELECT id,name,contractor_type
FROM contractors
ORDER BY name
");
$categories = $conn->query("
SELECT *
FROM work_categories
ORDER BY category_name
");
$activities = $conn->query("
SELECT wa.*,
       wc.category_name
FROM work_activities wa
LEFT JOIN work_categories wc ON wa.category_id=wc.id
ORDER BY wc.category_name, wa.activity_name
");
$activityData = [];
while ($a = $activities->fetch_assoc()) {
    $activityData[] = $a;
}
$items = $conn->query("
SELECT wi.*,
       wa.activity_name,
       wa.category_id
FROM work_items wi
LEFT JOIN work_activities wa ON wi.activity_id=wa.id
ORDER BY wi.item_no
");
$itemData = [];
while ($i = $items->fetch_assoc()) {
    $itemData[] = $i;
}
$list = $conn->query("
SELECT a.*,
       p.project_name,
       c.name contractor_name,
       c.contractor_type
FROM activities a
LEFT JOIN projects p ON a.project_id=p.id
LEFT JOIN contractors c ON a.contractor_id=c.id
ORDER BY a.id DESC
");
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="text-white fw-bold mb-1">Activities</h2>
        <small class="text-secondary">
            Integrated Planning Module
        </small>
    </div>
    <input type="text"
        class="form-control"
        style="max-width:280px"
        placeholder="Search..."
        onkeyup="filterActivities(this.value)">
</div>
<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success py-2">Activity added.</div>
<?php endif; ?>
<div class="card p-4 mb-4 shadow-sm border-0 rounded-4">
    <form method="post" class="row g-3 align-items-end">
        <div class="col-md-2">
            <label>Project</label>
            <select name="project_id" class="form-select" required>
                <option value="">Select</option>
                <?php while ($p = $projects->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= $p['project_name'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label>Contractor</label>
            <select name="contractor_id" class="form-select" required>
                <option value="">Select</option>
                <?php while ($c = $contractors->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>">
                        <?= $c['name'] ?> (<?= $c['contractor_type'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label>Category</label>
            <select id="categorySelect"
                class="form-select"
                onchange="loadActivities()" required>
                <option value="">Select</option>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['id'] ?>">
                        <?= $cat['category_name'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label>Activity</label>
            <select id="activitySelect"
                class="form-select"
                onchange="loadItems()" required>
                <option value="">Select Category</option>
            </select>
        </div>
        <div class="col-md-2">
            <label>Item</label>
            <select name="item_id"
                id="itemSelect"
                class="form-select"
                onchange="previewItem()"
                required>
                <option value="">Select Activity</option>
            </select>
        </div>
        <div class="col-md-2">
            <label>Target Qty</label>
            <input type="number"
                step="0.01"
                name="target_qty"
                class="form-control"
                value="100">
        </div>
        <div class="col-md-8">
            <label>Preview</label>
            <input type="text"
                id="previewBox"
                class="form-control"
                readonly
                placeholder="Auto filled item">
        </div>
        <div class="col-md-2">
            <button name="save"
                class="btn btn-gold w-100">
                Add
            </button>
        </div>
    </form>
</div>
<div class="card p-3 shadow-sm border-0 rounded-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0"
            id="activityTable">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Work Area</th>
                    <th>Contractor</th>
                    <th>Target Qty</th>
                    <th width="220">Progress</th>
                    <th>Status</th>
                    <th>Variance</th>
                    <th width="90">Action</th>
                </tr>
            </thead>
            <tbody>
<?php while ($r = $list->fetch_assoc()):
    $planned = 100;
    $actual  = (float)$r['progress'];
    $variance = $actual - $planned;
    if($variance >= 0){
        $status = 'On Track';
        $badge  = 'success';
    }
    elseif($variance >= -10){
        $status = 'Behind';
        $badge  = 'warning';
    }
    else{
        $status = 'Critical';
        $badge  = 'danger';
    }
?>
<tr>
    <td>
        <?= $r['project_name'] ?>
    </td>
    <td>
        <?= $r['description'] ?>
    </td>
    <td>
        <?= $r['contractor_name'] ?><br>
        <small class="text-secondary">
            <?= $r['contractor_type'] ?>
        </small>
    </td>
    <td>
        <?= number_format($r['target_qty'],2) ?>
    </td>
    <td>
        <div class="d-flex align-items-center gap-2">
            <div class="progress flex-grow-1"
                style="height:10px;">
                <div class="progress-bar bg-warning"
                    style="width:<?= $r['progress'] ?>%">
                </div>
            </div>
            <small>
                <?= number_format($r['progress'],2) ?>%
            </small>
        </div>
    </td>
    <td>
        <span class="badge bg-<?= $badge ?>">
            <?= $status ?>
        </span>
    </td>
    <td class="<?= $variance < 0 ? 'text-danger' : 'text-success' ?>">
        <?= number_format($variance,2) ?>%
    </td>
    <td>
        <a href="./?link=activities.php&delete=<?= $r['id'] ?>"
            onclick="return confirm('Delete activity?')"
            class="btn btn-sm btn-danger">
            <i class="bi bi-trash"></i>
        </a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
        </table>
    </div>
</div>
<script>
    const acts = <?= json_encode($activityData) ?>;
    const items = <?= json_encode($itemData) ?>;
    function loadActivities() {
        let cat = document.getElementById('categorySelect').value;
        let box = document.getElementById('activitySelect');
        box.innerHTML = '<option value="">Select Activity</option>';
        document.getElementById('itemSelect').innerHTML =
            '<option value="">Select Activity</option>';
        acts.forEach(a => {
            if (a.category_id == cat) {
                box.innerHTML += `
            <option value="${a.id}">
                ${a.activity_name}
            </option>`;
            }
        });
    }
    function loadItems() {
        let act = document.getElementById('activitySelect').value;
        let box = document.getElementById('itemSelect');
        box.innerHTML = '<option value="">Select Item</option>';
        let activity = acts.find(a => a.id == act);
        let activityName = activity ? activity.activity_name : '';
        items.forEach(i => {
            if (i.activity_id == act) {
                box.innerHTML += `
                <option value="${i.id}">
                    ${i.item_no} ${activityName} - ${i.item_name}
                </option>`;
            }
        });
    }
    function previewItem() {
        let id = document.getElementById('itemSelect').value;
        let found = items.find(x => x.id == id);
        if (found) {
            document.getElementById('previewBox').value =
                found.item_no + ' - ' + found.item_name +
                ' (' + found.unit.toUpperCase() + ')';
        }
    }
    function filterActivities(keyword) {
        keyword = keyword.toLowerCase();
        document.querySelectorAll("#activityTable tbody tr").forEach(row => {
            row.style.display =
                row.innerText.toLowerCase().includes(keyword) ?
                "" : "none";
        });
    }
</script>