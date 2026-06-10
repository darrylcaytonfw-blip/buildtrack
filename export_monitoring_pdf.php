<?php
require 'vendor/autoload.php';
include 'database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$res = $conn->query("
SELECT m.*,p.project_name,c.name contractor_name
FROM monitoring_entries m
LEFT JOIN projects p ON m.project_id=p.id
LEFT JOIN contractors c ON m.contractor_id=c.id
ORDER BY category_name,id ASC
");

$data = [];
$project = 'Monitoring Report';

while ($r = $res->fetch_assoc()) {
    $data[] = $r;
    if ($project == 'Monitoring Report' && $r['project_name']) $project = $r['project_name'];
}

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
        }

        .title {
            background: #1677a6;
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }

        .section {
            background: #eaf4fb;
            font-weight: bold;
        }

        .category {
            background: #20adf2;
            font-weight: bold;
            color: #FFF;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }
    </style>
</head>

<body>

    <table>
        <tr>
            <td colspan="8" class="title">MONITORING REPORT</td>
        </tr>
    </table>

    <table>
        <tr>
            <td colspan="8"><b>Project:</b> <?= $project ?></td>
        </tr>
        <tr>
            <td colspan="8"><b>Date:</b> <?= date('F d, Y h:i A') ?></td>
        </tr>
    </table>

    <table>
        <tr>
            <td colspan="8" class="section center">WORK SCHEDULE & BUDGET EXPENSES</td>
        </tr>
        <tr>
            <th>Scope</th>
            <th>Contractor</th>
            <th>Contract Amount</th>
            <th>Downpayment</th>
            <th>Billing</th>
            <th>Balance</th>
            <th>Target</th>
            <th>Actual</th>
        </tr>

        <?php $cat = '';
        foreach ($data as $r): if ($r['section_type'] != 'budget') continue; ?>
            <?php if ($cat != $r['category_name']): $cat = $r['category_name']; ?>
                <tr>
                    <td colspan="8" class="category"><?= strtoupper($cat) ?></td>
                </tr>
            <?php endif; ?>

            <tr>
                <td><?= $r['scope_of_work'] ?></td>
                <td><?= $r['contractor_name'] ?></td>
                <td class="right"><?= number_format($r['contract_amount'], 2) ?></td>
                <td class="right"><?= number_format($r['downpayment'], 2) ?></td>
                <td class="right"><?= number_format($r['progress_billing'], 2) ?></td>
                <td class="right"><?= number_format($r['balance'], 2) ?></td>
                <td class="center"><?= $r['target_acc'] ?></td>
                <td class="center"><?= $r['actual_acc'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <table>
        <tr>
            <td colspan="5" class="section center">DAILY / WEEKLY ACTIVITY MONITORING</td>
        </tr>

        <?php $cat = '';
        $scope = '';
        foreach ($data as $r): if ($r['section_type'] != 'daily') continue; ?>

            <?php if ($cat != $r['category_name']): $cat = $r['category_name'];
                $scope = ''; ?>
                <tr>
                    <td colspan="5" class="category"><?= strtoupper($cat) ?></td>
                </tr>
            <?php endif; ?>

            <?php if ($scope != $r['sub_scope']): $scope = $r['sub_scope']; ?>
                <tr>
                    <th><?= $scope ?></th>
                    <th>TARGET</th>
                    <th>ACTUAL</th>
                    <th colspan="2">% ACCOMPLISHMENT</th>
                </tr>
            <?php endif; ?>

            <tr>
                <td class="center"><?= $r['day_label'] ?></td>
                <td class="center"><?= $r['target_acc'] ?></td>
                <td class="center"><?= $r['actual_acc'] ?></td>
                <td colspan="2" class="center"><?= number_format($r['percent_accomplishment'], 2) ?>%</td>
            </tr>

        <?php endforeach; ?>
    </table>

</body>

</html>
<?php
$html = ob_get_clean();
$pdf = new Dompdf(new Options());
$pdf->loadHtml($html);
$pdf->setPaper('A4', 'landscape');
$pdf->render();
$pdf->stream("monitoring_report.pdf", ["Attachment" => true]);
