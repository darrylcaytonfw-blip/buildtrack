<?php
require 'vendor/autoload.php';
include 'database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/*
UPDATED export_schedule_pdf.php
Matches new Master Schedule:
- Discipline instead of Category
- No Project column in table
- Supports filters from GET
*/

$where = [];

if (!empty($_GET['project'])) {
    $project = mysqli_real_escape_string($conn, $_GET['project']);
    $where[] = "LOWER(p.project_name) = LOWER('$project')";
}

if (!empty($_GET['contractor'])) {
    $contractor = mysqli_real_escape_string($conn, $_GET['contractor']);
    $where[] = "LOWER(c.name) = LOWER('$contractor')";
}

if (!empty($_GET['discipline'])) {
    $discipline = mysqli_real_escape_string($conn, $_GET['discipline']);
    $where[] = "LOWER(wd.discipline_name) = LOWER('$discipline')";
}

if (!empty($_GET['progress'])) {
    $range = explode('-', $_GET['progress']);
    $min = (float)$range[0];
    $max = (float)$range[1];
    $where[] = "a.progress BETWEEN $min AND $max";
}

$condition = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$rows = $conn->query("
SELECT a.*,
       p.project_name,
       p.location,
       p.owner,
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
$condition
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

$data = [];
$meta = null;

while ($r = $rows->fetch_assoc()) {
    if (!$meta) $meta = $r;
    $data[] = $r;
}

function bgColor($status)
{
    switch ($status) {
        case 'tl':
            return '#dbeafe';
        case 'award':
            return '#bbf7d0';
        case 'catch':
            return '#fef08a';
        case 'standby':
            return '#fecaca';
        default:
            return '#ffffff';
    }
}

ob_start();
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 8px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 7px;
            color: #0369a1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 4px;
            vertical-align: middle;
        }

        th {
            background: #0369a1;
            color: #fff;
            text-align: center;
            font-weight: bold;
        }

        .title {
            background: #0369a1;
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }

        .meta td {
            border: none;
            font-size: 8px;
            padding: 3px;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>

<body>

    <table>
        <tr>
            <td colspan="22" class="title">MASTER SCHEDULE REPORT</td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td><b>Project:</b> <?= $meta['project_name'] ?? 'All Projects' ?></td>
            <td><b>Location:</b> <?= $meta['location'] ?? '-' ?></td>
            <td><b>Owner:</b> <?= $meta['owner'] ?? '-' ?></td>
            <td><b>Date:</b> <?= date('F d, Y h:i A') ?></td>
        </tr>
    </table>

    <table>
        <thead>

            <tr>
                <th rowspan="2" width="55">Discipline</th>
                <th rowspan="2" width="55">Activity</th>
                <th rowspan="2" width="40">Item</th>
                <th rowspan="2" width="80">Description</th>
                <th rowspan="2" width="60">Contractor</th>
                <th rowspan="2" width="35">Unit</th>
                <th rowspan="2" width="55">Target</th>
                <th colspan="12">WORK SCHEDULE</th>
                <th rowspan="2" width="55">Progress</th>
            </tr>

            <tr>
                <?php foreach ($months as $m): ?>
                    <th width="42"><?= $m ?></th>
                <?php endforeach; ?>
            </tr>

        </thead>

        <tbody>

            <?php foreach ($data as $r): ?>
                <tr>

                    <td><?= $r['discipline_name'] ?></td>
                    <td><?= $r['activity_name'] ?></td>
                    <td class="center"><?= $r['item_no'] ?></td>
                    <td><?= $r['description'] ?></td>
                    <td><?= $r['contractor_name'] ?></td>
                    <td class="center"><?= strtoupper($r['unit']) ?></td>

                    <td class="right">
                        <?= number_format($r['target_qty'], 2) ?>
                    </td>

                    <?php foreach ($months as $key => $label): ?>
                        <?php
                        $status = $r[$key] ?? '';
                        $pct = $r[$key . '_pct'] ?? '';
                        ?>
                        <td class="center"
                            style="background:<?= bgColor($status) ?>">
                            <?= ($pct !== '' && $pct !== null) ? number_format($pct, 2) . '%' : '' ?>
                        </td>
                    <?php endforeach; ?>

                    <td class="center bold">
                        <?= number_format($r['progress'], 2) ?>%
                    </td>

                </tr>
            <?php endforeach; ?>

        </tbody>
    </table>

    <table>
        <tr>
            <td width="25%" style="background:#dbeafe;">TL = Timeline</td>
            <td width="25%" style="background:#bbf7d0;">AW = Award</td>
            <td width="25%" style="background:#fef08a;">CU = Catch Up</td>
            <td width="25%" style="background:#fecaca;">SB = Standby</td>
        </tr>
    </table>

</body>

</html>

<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$pdf = new Dompdf($options);
$pdf->loadHtml($html);
$pdf->setPaper('legal', 'landscape');
$pdf->render();

$pdf->stream("master_schedule_report.pdf", [
    "Attachment" => true
]);
exit;
?>