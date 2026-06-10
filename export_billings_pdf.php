<?php
require 'vendor/autoload.php';
include 'database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/* -----------------------------
   GET AUTO PERIOD COLUMNS
------------------------------*/

$periods = [];

$getPeriods = $conn->query("
    SELECT DISTINCT DATE_FORMAT(billing_date,'%Y-%m') ym
    FROM billings
    WHERE billing_date IS NOT NULL
    ORDER BY ym
");

while ($p = $getPeriods->fetch_assoc()) {
    $periods[] = $p['ym'];
}

/* -----------------------------
   MAIN ROWS
------------------------------*/
$rows = $conn->query("
SELECT 
    ci.id item_id,
    c.name contractor_name,
    ci.activity_name,
    ci.contract_amount,

    IFNULL(SUM(CASE 
        WHEN b.billing_stage='Downpayment'
        THEN b.amount ELSE 0 END),0) dp,

    IFNULL(SUM(CASE 
        WHEN b.billing_stage <> 'Downpayment'
        THEN b.amount ELSE 0 END),0) billings_total,

    IFNULL(SUM(CASE
        WHEN b.status='Paid'
        THEN b.amount ELSE 0 END),0) amount_paid

FROM contractor_items ci
LEFT JOIN contractors c ON ci.contractor_id = c.id
LEFT JOIN billings b ON b.item_id = ci.id
GROUP BY ci.id
ORDER BY c.name, ci.activity_name
");

/* -----------------------------
   HTML
------------------------------*/
ob_start();
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 12px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #111827;
        }

        h2 {
            text-align: center;
            margin: 0 0 8px 0;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #9ca3af;
            padding: 4px 5px;
        }

        th {
            background: #d9ead3;
            text-align: center;
            font-weight: bold;
        }

        .section {
            background: #e2f0d9;
            font-weight: bold;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .total {
            background: #fce4d6;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <h2>CONTRACTOR BILLING SUMMARY</h2>

    <table>
        <thead>
            <tr>
                <th>CONTRACTORS</th>
                <th>SCOPE OF WORKS</th>
                <th>CONTRACT AMOUNT</th>
                <th>DOWNPAYMENT</th>
                <th>BILLINGS</th>

                <?php foreach ($periods as $per): ?>
                    <th>
                        <?= date('M Y', strtotime($per . '-01')) ?>
                    </th>
                <?php endforeach; ?>

                <th>AMOUNT PAID</th>
                <th>BALANCE</th>
                <th>TOTAL</th>
            </tr>
        </thead>

        <tbody>

            <tr class="section">
                <td colspan="<?= 8 + count($periods) ?>">
                    ITEM A (CONTRACTORS)
                </td>
            </tr>

            <?php
            $grandTotal = 0;
            $grandPaid  = 0;
            $grandBal   = 0;

            while ($r = $rows->fetch_assoc()):

                $contract = (float)$r['contract_amount'];
                $paid     = (float)$r['amount_paid'];
                $balance  = $contract - $paid;

                $grandTotal += $contract;
                $grandPaid  += $paid;
                $grandBal   += $balance;
            ?>
                <tr>

                    <td><?= $r['contractor_name'] ?></td>

                    <td><?= $r['activity_name'] ?></td>

                    <td class="right">
                        <?= number_format($contract, 2) ?>
                    </td>

                    <td class="right">
                        <?= number_format($r['dp'], 2) ?>
                    </td>

                    <td class="right">
                        <?= number_format($r['billings_total'], 2) ?>
                    </td>

                    <?php foreach ($periods as $per):

                        $q = $conn->query("
    SELECT IFNULL(SUM(amount),0) t
    FROM billings
    WHERE item_id = {$r['item_id']}
    AND DATE_FORMAT(billing_date,'%Y-%m') = '$per'
    AND status='Paid'
");

                        $amt = $q->fetch_assoc()['t'];
                    ?>

                        <td class="right">
                            <?= $amt > 0 ? number_format($amt, 2) : '' ?>
                        </td>

                    <?php endforeach; ?>

                    <td class="right">
                        <?= number_format($paid, 2) ?>
                    </td>

                    <td class="right">
                        <?= number_format($balance, 2) ?>
                    </td>

                    <td class="right">
                        <?= number_format($contract, 2) ?>
                    </td>

                </tr>
            <?php endwhile; ?>

            <tr class="total">

                <td colspan="<?= 5 + count($periods) ?>" class="right">
                    GRAND TOTAL
                </td>

                <td class="right">
                    <?= number_format($grandPaid, 2) ?>
                </td>

                <td class="right">
                    <?= number_format($grandBal, 2) ?>
                </td>

                <td class="right">
                    <?= number_format($grandTotal, 2) ?>
                </td>

            </tr>

        </tbody>
    </table>

</body>

</html>
<?php
$html = ob_get_clean();

/* -----------------------------
   PDF
------------------------------*/
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$pdf = new Dompdf($options);
$pdf->loadHtml($html);
$pdf->setPaper('A3', 'landscape');
$pdf->render();

$pdf->stream("contractor_billing_summary.pdf", [
    "Attachment" => true
]);
exit;
?>