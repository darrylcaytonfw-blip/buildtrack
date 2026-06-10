<?php
require 'vendor/autoload.php';
include 'database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid contractor ID.");

$contractor = $conn->query("SELECT * FROM contractors WHERE id=$id LIMIT 1")->fetch_assoc();
if (!$contractor) die("Contractor not found.");

$items = $conn->query("
SELECT ci.*, p.project_name
FROM contractor_items ci
LEFT JOIN projects p ON ci.project_id=p.id
WHERE ci.contractor_id=$id
ORDER BY ci.id ASC
");

$totalPrice = 0;
$totalDown  = 0;

ob_start();
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 10px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #334155;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 12px;
        }

        th,
        td {
            padding: 6px 7px;
            border-right: 1px solid #dbe2ea;
            border-bottom: 1px solid #dbe2ea;
            vertical-align: middle;
        }

        th:first-child,
        td:first-child {
            border-left: 1px solid #dbe2ea;
        }

        tr:first-child th {
            border-top: 1px solid #dbe2ea;
        }

        th {
            background: #1677a6;
            color: #fff;
            text-align: center;
            font-weight: bold;
        }

        .title {
            background: #1677a6;
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }

        .meta td {
            border: none !important;
            padding: 4px 8px;
        }

        .meta-label {
            width: 140px;
            font-weight: bold;
            color: #0f172a;
        }

        .total {
            background: #dcfce7;
            font-weight: bold;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }
    </style>
</head>

<body>

    <table>
        <tr>
            <td colspan="11" class="title">CONTRACTOR REPORT</td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td class="meta-label">Contractor Name</td>
            <td><?= $contractor['name'] ?></td>
        </tr>
        <tr>
            <td class="meta-label">Location</td>
            <td><?= $contractor['location'] ?></td>
        </tr>
        <tr>
            <td class="meta-label">Contact Person</td>
            <td><?= $contractor['contact_person'] ?></td>
        </tr>
        <tr>
            <td class="meta-label">Target Contract</td>
            <td>P <?= number_format($contractor['target_contract'], 2) ?></td>
        </tr>
        <tr>
            <td class="meta-label">Date</td>
            <td><?= date('F d, Y') ?></td>
        </tr>
    </table>

    <table>
        <tr>
            <th>Project</th>
            <th>Category</th>
            <th>Activity</th>
            <th>Planned (sqm)</th>
            <th>Actual (sqm)</th>
            <th>Variance</th>
            <th>Completion</th>
            <th>Price</th>
            <th>Downpayment</th>
            <th>Project Balance</th>
            <th>Remarks</th>
        </tr>

        <?php while ($r = $items->fetch_assoc()):
            $planned = (float)$r['sqm'];
            $actual = (float)$r['actual'];
            $variance = $planned > 0 ? (($planned - $actual) / $planned) * 100 : 0;
            $completion = $planned > 0 ? ($actual / $planned) * 100 : 0;
            $balance = $r['price'] - $r['downpayment'];
            $totalPrice += $r['price'];
            $totalDown  += $r['downpayment'];
        ?>
            <tr>
                <td><?= $r['project_name'] ?></td>
                <td><?= $r['work_category'] ?></td>
                <td><?= $r['activity_name'] ?></td>
                <td class="right"><?= number_format($planned, 2) ?></td>
                <td class="right"><?= number_format($actual, 2) ?></td>
                <td class="right"><?= number_format($variance, 2) ?>%</td>
                <td class="right"><?= number_format($completion, 2) ?>%</td>
                <td class="right"><?= number_format($r['price'], 2) ?></td>
                <td class="right"><?= number_format($r['downpayment'], 2) ?></td>
                <td class="right"><?= number_format($balance, 2) ?></td>
                <td></td>
            </tr>
        <?php endwhile; ?>

        <tr class="total">
            <td colspan="7" class="right">TOTAL</td>
            <td class="right"><?= number_format($totalPrice, 2) ?></td>
            <td class="right"><?= number_format($totalDown, 2) ?></td>
            <td class="right"><?= number_format($totalPrice - $totalDown, 2) ?></td>
            <td></td>
        </tr>

    </table>
</body>

</html>
<?php
$html = ob_get_clean();
$pdf = new Dompdf(new Options());
$pdf->loadHtml($html);
$pdf->setPaper('A4', 'landscape');
$pdf->render();
$pdf->stream("contractor_report_$id.pdf", ["Attachment" => true]);
