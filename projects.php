<?php
/* projects.php v2 - Full Project Master Setup */
include 'audit_helper.php';

/* =========================
SAVE PROJECT
========================= */
if (isset($_POST['save'])) {

  $stmt = $conn->prepare("
INSERT INTO projects(
 project_name, location, owner, subject,
 tower_count, floor_count, penthouse_count,
 studio_units, onebr_units, twobr_units, threebr_units, ph_units,
 studio_size, onebr_size, twobr_size, threebr_size, ph_size,
 parking_units, parking_size
)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
  ");

  $stmt->bind_param(
    "ssssiiiiiiiidddddid",
    $_POST['project_name'],
    $_POST['location'],
    $_POST['owner'],
    $_POST['subject'],
    $_POST['tower_count'],
    $_POST['floor_count'],
    $_POST['penthouse_count'],
    $_POST['studio_units'],
    $_POST['onebr_units'],
    $_POST['twobr_units'],
    $_POST['threebr_units'],
    $_POST['ph_units'],
    $_POST['studio_size'],
    $_POST['onebr_size'],
    $_POST['twobr_size'],
    $_POST['threebr_size'],
    $_POST['ph_size'],
    $_POST['parking_units'],
    $_POST['parking_size']
  );

  $stmt->execute();
  logAction($conn, 'CREATE', 'projects', $conn->insert_id, 'Created project ' . $_POST['project_name']);
  echo "<script>location='./?link=projects.php&added=1';</script>";
  exit;
}

/* =========================
UPDATE
========================= */
if (isset($_POST['update'])) {

  $id = (int)$_POST['id'];

  $stmt = $conn->prepare("
    UPDATE projects SET
      project_name=?,
      location=?,
      owner=?,
      subject=?,
      tower_count=?,
      floor_count=?,
      penthouse_count=?,
      studio_units=?,
      onebr_units=?,
      twobr_units=?,
      threebr_units=?,
      ph_units=?,
      studio_size=?,
      onebr_size=?,
      twobr_size=?,
      threebr_size=?,
      ph_size=?,
      parking_units=?,
      parking_size=?
    WHERE id=?
  ");
  $stmt->bind_param(
    "ssssiiiiiiiidddddidi",
    $_POST['project_name'],
    $_POST['location'],
    $_POST['owner'],
    $_POST['subject'],
    $_POST['tower_count'],
    $_POST['floor_count'],
    $_POST['penthouse_count'],
    $_POST['studio_units'],
    $_POST['onebr_units'],
    $_POST['twobr_units'],
    $_POST['threebr_units'],
    $_POST['ph_units'],
    $_POST['studio_size'],
    $_POST['onebr_size'],
    $_POST['twobr_size'],
    $_POST['threebr_size'],
    $_POST['ph_size'],
    $_POST['parking_units'],
    $_POST['parking_size'],
    $id
  );

  $stmt->execute();
  logAction($conn, 'UPDATE', 'projects', $id, 'Updated project ' . $_POST['project_name']);
  echo "<script>location='./?link=projects.php&updated=1';</script>";
  exit;
}
/* =========================
DELETE
========================= */
if (isset($_GET['delete'])) {

  $id = (int)$_GET['delete'];

  $conn->query("DELETE FROM projects WHERE id=$id");

  logAction($conn, 'DELETE', 'projects', $id, 'Deleted project');

  echo "<script>location='./?link=projects.php&deleted=1';</script>";
  exit;
}

$list = $conn->query("
    SELECT *
    FROM projects
    ORDER BY id DESC
");
?>
<style>
  label.small.text-secondary {
    font-size: 12px;
  }
</style>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h2 class="fw-bold text-white mb-1">Projects Master Setup</h2>
    <small class="text-secondary">
      Towers, inventory, workflows and structure
    </small>
  </div>

  <input type="text"
    class="form-control"
    style="max-width:260px"
    placeholder="Search project..."
    onkeyup="filterProjects(this.value)">
</div>

<?php if (isset($_GET['added'])): ?>
  <div class="alert alert-success py-2">Project created successfully.</div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
  <div class="alert alert-danger py-2">Project deleted successfully.</div>
<?php endif; ?>

<!-- =========================
FORM
========================= -->
<!-- REPLACE ONLY THE FORM SECTION WITH THIS PREMIUM LAYOUT -->

<div class="card p-4 mb-4">
  <form method="post" class="row g-4 align-items-end px-4">

    <!-- ROW 1 -->
    <div class="col-md-4">
      <label class="small text-secondary">Project Name</label>
      <input name="project_name" class="form-control" required>
    </div>

    <div class="col-md-2">
      <label class="small text-secondary">Tower</label>
      <input type="number" name="tower_count" class="form-control" value="1">
    </div>

    <div class="col-md-2">
      <label class="small text-secondary">Floors</label>
      <input type="number" name="floor_count" class="form-control" value="1">
    </div>

    <div class="col-md-2">
      <label class="small text-secondary">Location</label>
      <input name="location" class="form-control">
    </div>

    <div class="col-md-2">
      <label class="small text-secondary">Owner</label>
      <input name="owner" class="form-control">
    </div>

    <!-- HIDDEN -->
    <div class="col-md-2 d-none">
      <label>Penthouse Count</label>
      <input type="number" name="penthouse_count" class="form-control" value="2">
    </div>

    <!-- ROW 2 -->
    <div class="col-md-2">
      <label class="small text-secondary">Studio Unit</label>
      <input type="number" name="studio_units" class="form-control" value="0">
    </div>

    <div class="col-md-2">
      <label class="small text-secondary">1BR Unit</label>
      <input type="number" name="onebr_units" class="form-control" value="0">
    </div>

    <div class="col-md-2">
      <label class="small text-secondary">2BR Unit</label>
      <input type="number" name="twobr_units" class="form-control" value="0">
    </div>

    <div class="col-md-2">
      <label class="small text-secondary">3BR Unit</label>
      <input type="number" name="threebr_units" class="form-control" value="0">
    </div>

    <div class="col-md-2">
      <label class="small text-secondary">Penthouse Unit</label>
      <input type="number" name="ph_units" class="form-control" value="0">
    </div>

    <div class="col-md-2">
      <label class="small text-secondary">Parking Unit</label>
      <input type="number" name="parking_units" class="form-control" value="120">
    </div>

    <!-- ROW 3 -->
    <div class="col-md-2">
      <label class="small text-secondary">Studio Unit Size</label>
      <input type="number" step="0.01" name="studio_size" class="form-control" value="24">
    </div>

    <div class="col-md-2">
      <label class="small text-secondary">1BR Unit Size</label>
      <input type="number" step="0.01" name="onebr_size" class="form-control" value="36">
    </div>

    <div class="col-md-2">
      <label class="small text-secondary">2BR Unit Size</label>
      <input type="number" step="0.01" name="twobr_size" class="form-control" value="52">
    </div>

    <div class="col-md-2">
      <label class="small text-secondary">3BR Unit Size</label>
      <input type="number" step="0.01" name="threebr_size" class="form-control" value="65">
    </div>

    <div class="col-md-2">
      <label class="small text-secondary">Penthouse Unit Size</label>
      <input type="number" step="0.01" name="ph_size" class="form-control" value="120">
    </div>

    <div class="col-md-2">
      <label class="small text-secondary">Parking Unit Size</label>
      <input type="number" step="0.01" name="parking_size" class="form-control" value="120">
    </div>

    <!-- BUTTON -->
    <div class="col-md-2 ms-auto pt-2">
      <button name="save" class="btn btn-gold w-100 py-2">
        <i class="bi bi-plus-lg"></i> Save
      </button>
    </div>

  </form>

</div>

<!-- =========================
PROJECT LIST
========================= -->
<?php while ($r = $list->fetch_assoc()): ?>

  <?php
  $totalUnits =
    $r['studio_units'] +
    $r['onebr_units'] +
    $r['twobr_units'] +
    $r['threebr_units'] +
    $r['ph_units'];

  $totalArea =
    ($r['studio_units'] * $r['studio_size']) +
    ($r['onebr_units'] * $r['onebr_size']) +
    ($r['twobr_units'] * $r['twobr_size']) +
    ($r['threebr_units'] * $r['threebr_size']) +
    ($r['ph_units'] * $r['ph_size']);
  ?>

  <div class="card p-4 mb-4 project-card">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div>
        <h4 class="text-white mb-1"><?= $r['project_name'] ?></h4>
        <small class="text-secondary">
          <?= $r['location'] ?> • <?= $r['owner'] ?>
        </small>
      </div>
      <div>

        <a href="#"
          class="btn btn-sm btn-outline-light"
          data-bs-toggle="modal"
          data-bs-target="#edit<?= $r['id'] ?>">
          <i class="bi bi-pencil"></i>
        </a>
        <a href="./?link=projects.php&delete=<?= $r['id'] ?>"
          onclick="return confirm('Delete project?')"
          class="btn btn-sm btn-danger">
          <i class="bi bi-trash"></i>
        </a>
      </div>
    </div>

    <!-- PROJECT STRUCTURE -->
    <div class="row g-3 mb-4">

      <div class="col-md-4">
        <div class="card p-3 h-100">
          <small class="text-secondary">Tower Structure</small>
          <h5 class="mb-0"><?= $r['tower_count'] ?> Tower(s)</h5>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3 h-100">
          <small class="text-secondary">Floors</small>
          <h5 class="mb-0"><?= $r['floor_count'] ?> Floor(s)</h5>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3 h-100">
          <small class="text-secondary">Penthouse</small>
          <h5 class="mb-0"><?= $r['penthouse_count'] ?> Level(s)</h5>
        </div>
      </div>

    </div>

    <!-- INVENTORY -->
    <h5 class="text-warning mb-3">Project Inventory Structure</h5>

    <div class="table-responsive mb-4">
      <table class="table table-bordered align-middle">

        <tr>
          <th>Unit Type</th>
          <th>Size (sqm)</th>
          <th># Units</th>
          <th>Total Area</th>
        </tr>

        <tr>
          <td>Studio</td>
          <td class="text-num"><?= $r['studio_size'] ?></td>
          <td class="text-num"><?= $r['studio_units'] ?></td>
          <td><?= number_format($r['studio_units'] * $r['studio_size'], 2) ?></td>
        </tr>

        <tr>
          <td>1-BR</td>
          <td class="text-num"><?= $r['onebr_size'] ?></td>
          <td class="text-num"><?= $r['onebr_units'] ?></td>
          <td class="text-num"><?= number_format($r['onebr_units'] * $r['onebr_size'], 2) ?></td>
        </tr>

        <tr>
          <td>2-BR</td>
          <td class="text-num"><?= $r['twobr_size'] ?></td>
          <td class="text-num"><?= $r['twobr_units'] ?></td>
          <td class="text-num"><?= number_format($r['twobr_units'] * $r['twobr_size'], 2) ?></td>
        </tr>

        <tr>
          <td>3-BR</td>
          <td class="text-num"><?= $r['threebr_size'] ?></td>
          <td class="text-num"><?= $r['threebr_units'] ?></td>
          <td class="text-num"><?= number_format($r['threebr_units'] * $r['threebr_size'], 2) ?></td>
        </tr>

        <tr>
          <td>Penthouse Unit</td>
          <td class="text-num"><?= $r['ph_size'] ?></td>
          <td class="text-num"><?= $r['ph_units'] ?></td>
          <td class="text-num"><?= number_format($r['ph_units'] * $r['ph_size'], 2) ?></td>
        </tr>

        <tr class="fw-bold">
          <td>TOTAL</td>
          <td class="text-num">—</td>
          <td class="text-num"><?= $totalUnits ?></td>
          <td class="text-num"><?= number_format($totalArea, 2) ?> sqm</td>
        </tr>

      </table>
    </div>

    <!-- STATIC BUSINESS RULES -->
    <div class="row g-3">

      <div class="col-md-4">
        <div class="card p-3 h-100">
          <h6 class="text-warning">User Roles</h6>
          <small>Management, CEO, Project Manager, Finance, Engineer, Supplier, Admin</small>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3 h-100">
          <h6 class="text-info">Functions</h6>
          <small>Worker, Checker, Verifier, Approver</small>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3 h-100">
          <h6 class="text-success">Status Flow</h6>
          <small>Requested → Ongoing → Checked → Verified → Completed → Delivered</small>
        </div>
      </div>

    </div>

  </div>
  <div class="modal fade" id="edit<?= $r['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <form method="post">

          <div class="modal-header">
            <h5 class="modal-title">Edit Project</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">

            <div class="row g-3 align-items-end">

              <div class="col-md-8">
                <label>Project Name</label>
                <input name="project_name" class="form-control" value="<?= $r['project_name'] ?>">
              </div>

              <div class="col-md-2">
                <label>Towers</label>
                <input type="number" name="tower_count" class="form-control" value="<?= $r['tower_count'] ?>">
              </div>

              <div class="col-md-2">
                <label>Floors</label>
                <input type="number" name="floor_count" class="form-control" value="<?= $r['floor_count'] ?>">
              </div>

              <div class="col-md-4">
                <label>Location</label>
                <input name="location" class="form-control" value="<?= $r['location'] ?>">
              </div>

              <div class="col-md-4">
                <label>Owner</label>
                <input name="owner" class="form-control" value="<?= $r['owner'] ?>">
              </div>

              <div class="col-md-4">
                <label>Penthouse</label>
                <input type="number" name="penthouse_count" class="form-control" value="<?= $r['penthouse_count'] ?>">
              </div>

              <div class="col-md-3">
                <label>Studio Units</label>
                <input type="number" name="studio_units" class="form-control" value="<?= $r['studio_units'] ?>">
              </div>

              <div class="col-md-3">
                <label>Studio Size</label>
                <input type="number" step="0.01" name="studio_size" class="form-control" value="<?= $r['studio_size'] ?>">
              </div>

              <div class="col-md-3">
                <label>1BR Units</label>
                <input type="number" name="onebr_units" class="form-control" value="<?= $r['onebr_units'] ?>">
              </div>

              <div class="col-md-3">
                <label>1BR Size</label>
                <input type="number" step="0.01" name="onebr_size" class="form-control" value="<?= $r['onebr_size'] ?>">
              </div>

              <div class="col-md-3">
                <label>2BR Units</label>
                <input type="number" name="twobr_units" class="form-control" value="<?= $r['twobr_units'] ?>">
              </div>

              <div class="col-md-3">
                <label>2BR Size</label>
                <input type="number" step="0.01" name="twobr_size" class="form-control" value="<?= $r['twobr_size'] ?>">
              </div>

              <div class="col-md-3">
                <label>3BR Units</label>
                <input type="number" name="threebr_units" class="form-control" value="<?= $r['threebr_units'] ?>">
              </div>

              <div class="col-md-3">
                <label>3BR Size</label>
                <input type="number" step="0.01" name="threebr_size" class="form-control" value="<?= $r['threebr_size'] ?>">
              </div>

              <div class="col-md-6">
                <label>Subject</label>
                <input name="subject" class="form-control" value="<?= $r['subject'] ?>">
              </div>

              <div class="col-md-3">
                <label>Penthouse Units</label>
                <input type="number" name="ph_units" class="form-control" value="<?= $r['ph_units'] ?>">
              </div>

              <div class="col-md-3">
                <label>Penthouse Size</label>
                <input type="number" step="0.01" name="ph_size" class="form-control" value="<?= $r['ph_size'] ?>">
              </div>
              <div class="col-md-3">
                <label>Parking Units</label>
                <input type="number" name="parking_units"
                  class="form-control"
                  value="<?= $r['parking_units'] ?>" min="0">


              </div>

              <div class="col-md-3">

                <label>Parking Size</label>
                <input type="number" step="0.01"
                  name="parking_size"
                  class="form-control"
                  value="<?= $r['parking_size'] ?>" min="0">
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button name="update" class="btn btn-gold">Save Changes</button>
          </div>

        </form>
      </div>
    </div>
  </div>

<?php endwhile; ?>
<div class="card p-4 my-4">
  <h5 class="mb-3">Edit History</h5>

  <div class="table-responsive">
    <table class="table table-hover">

      <thead>
        <tr>
          <th>ID Number</th>
          <th>Full Name</th>
          <th>What Happened</th>
          <th>Project ID</th>
          <th>Notes</th>
          <th>Date & Time</th>
        </tr>
      </thead>

      <tbody>

        <?php
        $logs = $conn->query("
SELECT user_id, username, action, record_id, details, log_time
FROM audit_logs
WHERE module_name='projects'
ORDER BY id DESC
LIMIT 20
");

        while ($log = $logs->fetch_assoc()):
        ?>

          <tr>
            <td><?= $log['user_id'] ?></td>
            <td><?= ucfirst($log['username']) ?></td>
            <td>Changed Record</td>
            <td><?= $log['record_id'] ?></td>
            <td><?= $log['details'] ?></td>
            <td><?= date('M d, Y g:i A', strtotime($log['log_time'])) ?></td>
          </tr>

        <?php endwhile; ?>

      </tbody>
    </table>
  </div>
</div>
<script>
  function filterProjects(keyword) {
    keyword = keyword.toLowerCase();

    document.querySelectorAll(".project-card").forEach(card => {
      card.style.display =
        card.innerText.toLowerCase().includes(keyword) ? "" : "none";
    });
  }
</script>