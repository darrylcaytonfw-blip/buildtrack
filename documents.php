<?php
/* documents.php - Full Documents Module */
include 'audit_helper.php';
$isManagement = isset($_SESSION['role']) && $_SESSION['role'] === 'management';

$uploadDir = 'uploads/documents/';

/* Create folder if missing */
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* =========================
UPLOAD
========================= */
if (isset($_POST['save'])) {

    $project_id = (int)$_POST['project_id'];
    $title      = trim($_POST['title']);
    $category   = trim($_POST['category']);
    $uploadedBy = $_SESSION['user'] ?? 'system';

    if (!empty($_FILES['file']['name'])) {

        $file      = $_FILES['file'];
        $original  = $file['name'];
        $tmp       = $file['tmp_name'];
        $size      = $file['size'];
        $ext       = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowed)) {
            echo "<div class='alert alert-danger'>Invalid file type.</div>";
        } else {

            $newName = time() . '_' . rand(1000, 9999) . '.' . $ext;

            if (move_uploaded_file($tmp, $uploadDir . $newName)) {

                $stmt = $conn->prepare("
                    INSERT INTO documents
                    (project_id,title,file_name,category,uploaded_by)
                    VALUES (?,?,?,?,?)
                ");

                $stmt->bind_param(
                    "issss",
                    $project_id,
                    $title,
                    $newName,
                    $category,
                    $uploadedBy
                );

                $stmt->execute();

                $newId = $conn->insert_id;

                logAction(
                    $conn,
                    'UPLOAD',
                    'documents',
                    $newId,
                    'Uploaded file ' . $title
                );

                echo "<script>location='./?link=documents.php&added=1';</script>";
                exit;
            }
        }
    }
}

/* =========================
DELETE
========================= */
if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    $row = $conn->query("
        SELECT file_name,title
        FROM documents
        WHERE id=$id
        LIMIT 1
    ")->fetch_assoc();

    if ($row) {

        $filePath = $uploadDir . $row['file_name'];

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $conn->query("DELETE FROM documents WHERE id=$id");

        logAction(
            $conn,
            'DELETE',
            'documents',
            $id,
            'Deleted file ' . $row['title']
        );
    }

    echo "<script>location='./?link=documents.php&deleted=1';</script>";
    exit;
}

/* =========================
DATA
========================= */
$projects = $conn->query("
    SELECT id, project_name
    FROM projects
    ORDER BY project_name
");

$list = $conn->query("
    SELECT d.*, p.project_name
    FROM documents d
    LEFT JOIN projects p ON d.project_id = p.id
    ORDER BY d.id DESC
");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="text-white fw-bold mb-1">Documents</h2>
        <small class="text-secondary">
            Contracts, invoices, plans, receipts and files
        </small>
    </div>

    <input type="text"
        class="form-control"
        style="max-width:260px"
        placeholder="Search documents..."
        onkeyup="filterDocs(this.value)">
</div>

<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success py-2">
        File uploaded successfully.
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger py-2">
        File deleted successfully.
    </div>
<?php endif; ?>

<!-- =========================
UPLOAD FORM
========================= -->
<?php if (!$isManagement): ?>
    <div class="card p-4 mb-4">
        <form method="post"
            enctype="multipart/form-data"
            class="row g-3 align-items-end">

            <div class="col-md-3">
                <label class="small text-secondary mb-1">Project</label>
                <select name="project_id" class="form-select select2" required>
                    <option value="">Select Project</option>
                    <?php while ($p = $projects->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= $p['project_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="small text-secondary mb-1">Title</label>
                <input type="text"
                    name="title"
                    class="form-control"
                    required>
            </div>

            <div class="col-md-2">
                <label class="small text-secondary mb-1">Category</label>
                <select name="category" class="form-select">
                    <option>Contract</option>
                    <option>Invoice</option>
                    <option>Receipt</option>
                    <option>Plan</option>
                    <option>Photo</option>
                    <option>Permit</option>
                    <option>Other</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="small text-secondary mb-1">File</label>
                <input type="file"
                    name="file"
                    class="form-control"
                    required>
            </div>

            <div class="col-md-1">
                <button name="save"
                    class="btn btn-gold w-100">
                    <i class="bi bi-upload"></i>
                </button>
            </div>

        </form>
    </div>
<?php endif; ?>
<!-- =========================
TABLE
========================= -->
<div class="card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="docTable">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Project</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th width="170">Action</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($r = $list->fetch_assoc()): ?>
                    <tr>

                        <td><?= $r['id'] ?></td>

                        <td><?= $r['project_name'] ?></td>

                        <td><?= $r['title'] ?></td>

                        <td>
                            <span class="badge bg-primary">
                                <?= $r['category'] ?>
                            </span>
                        </td>

                        <td><?= $r['uploaded_by'] ?></td>

                        <td>
                            <?= date('M d, Y', strtotime($r['uploaded_at'])) ?>
                        </td>

                        <td class="d-flex gap-1">

                            <a href="uploads/documents/<?= $r['file_name'] ?>"
                                target="_blank"
                                class="btn btn-sm btn-success">
                                <i class="bi bi-eye"></i>
                            </a>

                            <a href="uploads/documents/<?= $r['file_name'] ?>"
                                download
                                class="btn btn-sm btn-primary">
                                <i class="bi bi-download"></i>
                            </a>

                            <?php if (!$isManagement): ?>

                                <a href="./?link=documents.php&delete=<?= $r['id'] ?>"
                                    onclick="return confirm('Delete file?')"
                                    class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </a>

                            <?php endif; ?>

                        </td>

                    </tr>
                <?php endwhile; ?>
            </tbody>

        </table>
    </div>
</div>

<script>
    function filterDocs(keyword) {
        keyword = keyword.toLowerCase();

        document.querySelectorAll("#docTable tbody tr").forEach(row => {
            row.style.display =
                row.innerText.toLowerCase().includes(keyword) ?
                "" :
                "none";
        });
    }
</script>