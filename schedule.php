<?php
include 'database.php';
/*
FULL UPDATED MASTER SCHEDULE
*/
$projects = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name");
$contractors = $conn->query("SELECT id, name FROM contractors ORDER BY name");
$disciplines = $conn->query("SELECT id, discipline_name FROM work_disciplines ORDER BY discipline_name");
$rows = $conn->query("
SELECT a.*,
       p.project_name,
       c.name AS contractor_name,
       wi.unit,
       wa.activity_name,
       wd.discipline_name
FROM activities a
LEFT JOIN projects p          ON a.project_id = p.id
LEFT JOIN contractors c       ON a.contractor_id = c.id
LEFT JOIN work_items wi       ON a.item_id = wi.id
LEFT JOIN work_activities wa  ON wi.activity_id = wa.id
LEFT JOIN work_disciplines wd ON wa.discipline_id = wd.id
ORDER BY p.project_name, a.item_no
");
$months = [
    'jan'  => 'JAN',
    'feb'  => 'FEB',
    'mar'  => 'MAR',
    'apr'  => 'APR',
    'may'  => 'MAY',
    'jun'  => 'JUN',
    'jul'  => 'JUL',
    'aug'  => 'AUG',
    'sep'  => 'SEP',
    'oct'  => 'OCT',
    'nov'  => 'NOV',
    'decm' => 'DEC'
];
?>
<style>
    body {
        background: #08142d;
        font-family: Segoe UI, sans-serif;
    }

    .schedule-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .schedule-header h1 {
        color: #fff;
        font-weight: 800;
        margin: 0;
    }

    .schedule-header small {
        color: #cbd5e1;
    }

    .export-btn {
        background: #0d6efd;
        color: #fff;
        border: none;
        padding: 12px 18px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
    }

    .filter-box {
        background: #fff;
        padding: 20px;
        border-radius: 18px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, .10);
        margin-bottom: 20px;
    }

    .schedule-wrap {
        background: #fff;
        border-radius: 18px;
        padding: 15px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, .10);
    }

    .schedule-table {
        min-width: 2200px;
    }

    .schedule-table th {
        font-size: 13px;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 5;
    }

    .schedule-table td {
        font-size: 13px;
        vertical-align: middle;
        white-space: nowrap;
    }

    .month-box {
        min-width: 85px;
        text-align: center;
        font-weight: 700;
    }

    .tl {
        background: #dbeafe;
    }

    .award {
        background: #bbf7d0;
    }

    .catch {
        background: #fef08a;
    }

    .standby {
        background: #fecaca;
    }

    .progress-pill {
        padding: 6px 12px;
        border-radius: 30px;
        background: #16a34a;
        color: #fff;
        font-weight: 700;
    }

    .sticky-right {
        position: sticky;
        right: 0;
        background: #fff;
        z-index: 3;
    }
</style>
<div class="container-fluid py-4">
    <div class="schedule-header">
        <div>
            <h1>Master Schedule</h1>
            <small>Project Monitoring Dashboard</small>
        </div>
        <a href="#" onclick="exportPDF()" class="export-btn">
            PDF Export
        </a>
        <script>
            function exportPDF() {
                let project = document.getElementById("projectFilter").value;
                let contractor = document.getElementById("contractorFilter").value;
                let discipline = document.getElementById("disciplineFilter").value;
                let progress = document.getElementById("progressFilter").value;
                let url = "export_schedule_pdf.php?";
                if (project) url += "project=" + encodeURIComponent(project) + "&";
                if (contractor) url += "contractor=" + encodeURIComponent(contractor) + "&";
                if (discipline) url += "discipline=" + encodeURIComponent(discipline) + "&";
                if (progress) url += "progress=" + encodeURIComponent(progress) + "&";
                window.open(url, "_blank");
            }
        </script>
    </div>
    <!-- FILTERS -->
    <div class="filter-box">
        <div class="row g-2">
            <!-- Project -->
            <div class="col-md-3">
                <select id="projectFilter" class="form-select" onchange="applyFilters()">
                    <option value="">All Projects</option>
                    <?php while ($p = $projects->fetch_assoc()): ?>
                        <option value="<?= strtolower($p['project_name']) ?>">
                            <?= $p['project_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <!-- Contractor -->
            <div class="col-md-3">
                <select id="contractorFilter" class="form-select" onchange="applyFilters()">
                    <option value="">All Contractors</option>
                    <?php while ($c = $contractors->fetch_assoc()): ?>
                        <option value="<?= strtolower($c['name']) ?>">
                            <?= $c['name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <!-- Discipline -->
            <div class="col-md-3">
                <select id="disciplineFilter" class="form-select" onchange="applyFilters()">
                    <option value="">All Disciplines</option>
                    <?php while ($d = $disciplines->fetch_assoc()): ?>
                        <option value="<?= strtolower($d['discipline_name']) ?>">
                            <?= $d['discipline_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <!-- Progress -->
            <div class="col-md-3">
                <select id="progressFilter" class="form-select" onchange="applyFilters()">
                    <option value="">All Progress</option>
                    <option value="0-25">0% - 25%</option>
                    <option value="26-50">26% - 50%</option>
                    <option value="51-75">51% - 75%</option>
                    <option value="76-100">76% - 100%</option>
                </select>
            </div>
        </div>
    </div>
    <!-- TABLE -->
    <div class="schedule-wrap" style="background-color: transparent;">
        <div class="mb-3">
            <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-3"
                onclick="toggleFullscreen(this)">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>
            <h4 class="fw-bold mb-1">
                Schedule Performance Monitoring
            </h4>
            <small class="text-secondary">
                Overall schedule performance, variance analysis and recovery action tracking
            </small>

        </div>
        <div class="table-responsive" style="max-height:80vh; overflow:auto;">
            <table class="table table-bordered schedule-table align-middle mb-0" id="scheduleTable">
                <thead>
                    <tr>
                        <th>Discipline</th>
                        <th>Activity</th>
                        <th>Item No</th>
                        <th>Description</th>
                        <th>Contractor</th>
                        <th>Unit</th>
                        <th>Target</th>
                        <th>Planned %</th>
                        <th>Actual %</th>
                        <th>Variance %</th>
                        <th>Status</th>
                        <th>Issue / Cause</th>
                        <th>Corrective Action</th>
                        <th>Recovery Date</th>
                        <?php foreach ($months as $m): ?>
                            <th><?= $m ?></th>
                        <?php endforeach; ?>
                        <th class="sticky-right">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $rows->fetch_assoc()): ?>
                        <tr
                            data-project="<?= strtolower($r['project_name']) ?>"
                            data-contractor="<?= strtolower($r['contractor_name']) ?>"
                            data-discipline="<?= strtolower($r['discipline_name']) ?>"
                            data-progress="<?= $r['progress'] ?>">
                            <td><?= $r['discipline_name'] ?></td>
                            <td><?= $r['activity_name'] ?></td>
                            <td><?= $r['item_no'] ?></td>
                            <td><?= $r['description'] ?></td>
                            <td><?= $r['contractor_name'] ?></td>
                            <td><?= strtoupper($r['unit']) ?></td>
                            <td class="text-end"><?= number_format($r['target_qty'], 2) ?></td>
                            <?php
                            $planned = 100;
                            $actual  = (float)$r['progress'];
                            $variance = $actual - $planned;
                            if ($variance >= 0) {
                                $status = 'On Track';
                                $issue  = '-';
                                $action = 'Continue monitoring';
                            } elseif ($variance >= -10) {
                                $status = 'Behind';
                                $issue  = 'Manpower gap';
                                $action = 'Deploy additional crew';
                            } else {
                                $status = 'Critical';
                                $issue  = 'Material delay';
                                $action = 'Expedite supplier delivery';
                            }
                            ?>
                            <td>
                                <?= number_format($planned, 0) ?>%
                            </td>
                            <td>
                                <?= number_format($actual, 0) ?>%
                            </td>
                            <td class="<?= $variance < 0 ? 'text-danger' : 'text-success' ?>">
                                <?= number_format($variance, 0) ?>%
                            </td>
                            <td>
                                <span class="badge bg-<?= $variance < -10 ? 'danger' : ($variance < 0 ? 'warning text-dark' : 'success') ?>">
                                    <?= $status ?>
                                </span>
                            </td>
                            <td>
                                <?= $issue ?>
                            </td>
                            <td>
                                <?= $action ?>
                            </td>
                            <td>
                                <?= date('d-M-Y', strtotime('+14 days')) ?>
                            </td>
                            <?php foreach ($months as $key => $val): ?>
                                <?php
                                $status = $r[$key];
                                $pct = $r[$key . '_pct'];
                                ?>
                                <td class="month-box <?= $status ?>">
                                    <?= ($pct !== null && $pct !== '') ? number_format($pct, 2) . '%' : '-' ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="sticky-right text-center">
                                <span class="progress-pill">
                                    <?= number_format($r['progress'], 2) ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    function applyFilters() {
        let project = document.getElementById("projectFilter").value;
        let contractor = document.getElementById("contractorFilter").value;
        let discipline = document.getElementById("disciplineFilter").value;
        let progress = document.getElementById("progressFilter").value;
        let rows = document.querySelectorAll("#scheduleTable tbody tr");
        rows.forEach(row => {
            let show = true;
            let rowProject = row.dataset.project;
            let rowContractor = row.dataset.contractor;
            let rowDiscipline = row.dataset.discipline;
            let rowProgress = parseFloat(row.dataset.progress);
            if (project && rowProject !== project) show = false;
            if (contractor && rowContractor !== contractor) show = false;
            if (discipline && rowDiscipline !== discipline) show = false;
            if (progress) {
                let range = progress.split("-");
                let min = parseFloat(range[0]);
                let max = parseFloat(range[1]);
                if (rowProgress < min || rowProgress > max) {
                    show = false;
                }
            }
            row.style.display = show ? "" : "none";
        });
    }
</script>