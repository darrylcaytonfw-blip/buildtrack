<style>
    .po-wrapper {
        background: #f5f7fb;
        border: 1px solid #d8e1ef;
        border-radius: 26px;
        overflow: hidden;
        color: #12284a;
        font-family: Inter, sans-serif;
    }

    /* HEADER */

    .po-header {
        display: flex;
        justify-content: space-between;
        gap: 30px;
        padding: 24px 28px;
        background: #f3f6fb;
        border-bottom: 1px solid #d8e1ef;
    }

    .po-company {
        display: flex;
        gap: 14px;
        align-items: flex-start;
    }

    .po-logo {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: #08244c;
        color: #fff;
        font-size: 18px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .po-company h2 {
        margin: 0 0 6px;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.2;
        color: #0f2342;
    }

    .po-company p {
        margin: 2px 0;
        font-size: 11px;
        font-weight: 500;
        color: #60708a;
        line-height: 1.45;
    }

    .po-title {
        text-align: right;
    }

    .po-title h1 {
        margin: 0;
        font-size: 23px;
        font-weight: 800;
        letter-spacing: .4px;
        color: #08244c;
    }

    .po-title span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 10px 0 14px;
        padding: 6px 14px;
        border-radius: 999px;
        background: #edf4fc;
        border: 1px solid #cdddf1;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 1px;
        color: #17457d;
    }

    .po-title p {
        margin: 3px 0;
        font-size: 11px;
        font-weight: 500;
        color: #60708a;
    }

    /* INFO CARDS */

    .po-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        padding: 22px 28px 12px;
    }

    .po-card {
        background: #f7f9fc;
        border: 1px solid #d9e2ef;
        border-radius: 18px;
        padding: 16px 18px;
    }

    .po-card h4 {
        margin: 0 0 12px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1px;
        color: #08244c;
    }

    .po-card strong {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        font-weight: 700;
        color: #132847;
    }

    .po-card p {
        margin: 3px 0;
        font-size: 11px;
        font-weight: 500;
        line-height: 1.5;
        color: #56667f;
    }

    /* TABLE */

    .po-table {
        width: auto !important;
        min-width: 94%;
        margin: 10px 28px 0;
        border-radius: 16px;
        overflow: hidden;
        table-layout: auto;
        border-collapse: separate;
        border-spacing: 0;
    }

    .po-table thead th {
        background: #082c59 !important;
        color: #fff !important;
        border: none !important;
        padding: 11px 14px !important;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .4px;
        white-space: nowrap;
    }

    .po-table tbody td {
        background: #fff;
        border-color: #d9e2ef;
        padding: 10px 14px !important;
        font-size: 12px;
        font-weight: 500;
        color: #213554;
        vertical-align: middle;
        white-space: nowrap;
    }

    .po-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* DESCRIPTION + SPECS */
    .po-table th:nth-child(1),
    .po-table td:nth-child(1) {
        width: 60px;
    }

    .po-table th:nth-child(4),
    .po-table td:nth-child(4) {
        width: 90px;
    }

    .po-table th:nth-child(5),
    .po-table td:nth-child(5) {
        width: 90px;
    }

    .po-table th:nth-child(6),
    .po-table td:nth-child(6),

    .po-table th:nth-child(7),
    .po-table td:nth-child(7) {
        width: 150px;
    }

    .po-table th:nth-child(2),
    .po-table td:nth-child(2) {
        width: 34%;
        white-space: normal;
        line-height: 1.5;
    }

    .po-table th:nth-child(3),
    .po-table td:nth-child(3) {
        width: 32%;
        white-space: normal;
        line-height: 1.5;
    }

    /* QTY */

    .po-table th:nth-child(4),
    .po-table td:nth-child(4) {
        width: 70px;
    }

    /* UNIT */

    .po-table th:nth-child(5),
    .po-table td:nth-child(5) {
        width: 70px;
    }

    /* PRICE */

    .po-table th:nth-child(6),
    .po-table td:nth-child(6),

    .po-table th:nth-child(7),
    .po-table td:nth-child(7) {
        width: 140px;
    }

    /* BETTER NUMBERS */

    .po-table .text-end {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    /* LOWER SECTION */

    .po-bottom {
        display: grid;
        grid-template-columns: 1.3fr .95fr;
        gap: 16px;
        padding: 18px 28px 0;
    }

    .po-terms {
        border: 1px dashed #b9c9dd;
        border-radius: 18px;
        padding: 16px;
        background: #fafcff;
    }

    .po-terms h5 {
        margin: 0 0 8px;
        font-size: 12px;
        font-weight: 700;
        color: #102544;
    }

    .po-terms p {
        margin: 0;
        font-size: 10.5px;
        line-height: 1.7;
        font-weight: 500;
        color: #5d6c84;
    }

    /* SUMMARY */

    .po-summary {
        border: 1px solid #d8e1ef;
        border-radius: 18px;
        overflow: hidden;
        background: #fff;
    }

    .sum-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 11px 14px;
        border-bottom: 1px solid #e4ebf5;
        font-size: 12px;
        font-weight: 500;
        color: #233655;
    }

    .sum-row strong {
        font-weight: 700;
        color: #102544;
    }

    .sum-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px;
        background: #08244c;
        color: #fff;
    }

    .sum-total span {
        font-size: 13px;
        font-weight: 700;
    }

    .sum-total strong {
        font-size: 15px;
        font-weight: 800;
    }

    /* SIGNATURES */

    .po-signatures {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 16px;
        padding: 30px 28px 26px;
    }

    .sig-box {
        font-size: 11px;
    }

    .sig-line {
        height: 1px;
        background: #7e8ea8;
        margin-bottom: 10px;
    }

    .sig-box strong {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #102544;
    }

    .sig-box p {
        margin: 2px 0 0;
        font-size: 10px;
        font-weight: 500;
        color: #60708a;
    }

    /* DOCUMENT HEADER */

    .doc-pack-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        padding: 28px 26px;
        margin-bottom: 24px;
        background: #f7f9fc;
        border: 1px solid #d8e1ef;
        border-radius: 28px;
    }

    .doc-pack-header h1 {
        margin: 0 0 6px;
        font-size: 28px;
        font-weight: 800;
        color: #102544;
    }

    .doc-pack-header p {
        margin: 0;
        font-size: 14px;
        font-weight: 500;
        color: #697a94;
    }

    .doc-pack-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .btn-pdf,
    .btn-review {
        height: 50px;
        padding: 0 22px;
        border-radius: 18px;
        font-size: 14px;
        font-weight: 700;
        border: none;
        transition: .2s ease;
    }

    .btn-pdf {
        background: #08244c;
        color: #fff;
    }

    .btn-review {
        background: #fff;
        color: #102544;
        border: 1px solid #cfdced;
    }

    /* STATS */

    .doc-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
        margin-bottom: 22px;
    }

    .doc-stat-card {
        padding: 22px 22px 20px;
        background: #f7f9fc;
        border: 1px solid #d8e1ef;
        border-radius: 26px;
    }

    .doc-stat-card span {
        display: block;
        margin-bottom: 12px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1px;
        color: #5d7090;
    }

    .doc-stat-card h2 {
        margin: 0 0 10px;
        font-size: 24px;
        font-weight: 800;
        color: #08244c;
    }

    .doc-stat-card p {
        margin: 0;
        font-size: 12px;
        font-weight: 500;
        color: #00805c;
    }

    /* EMPTY */

    .empty-po-state {
        padding: 70px 30px;
        border-radius: 30px;
        border: 1px dashed #cfd9e7;
        background: #f8fbff;
        text-align: center;
    }

    .empty-po-icon {
        width: 82px;
        height: 82px;
        margin: 0 auto 22px;
        border-radius: 24px;
        background: #eaf2fb;
        color: #17457d;
        font-size: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .empty-po-state h2 {
        margin: 0 0 10px;
        font-size: 24px;
        font-weight: 800;
        color: #102544;
    }

    .empty-po-state p {
        max-width: 520px;
        margin: auto;
        font-size: 14px;
        line-height: 1.7;
        font-weight: 500;
        color: #60708a;
    }

    /* RESPONSIVE */

    @media(max-width:991px) {

        .po-header {
            flex-direction: column;
        }

        .po-info-grid,
        .po-bottom,
        .po-signatures,
        .doc-stats {
            grid-template-columns: 1fr;
        }

        .po-title {
            text-align: left;
        }

        .po-title h1 {
            font-size: 20px;
        }

        .doc-pack-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .doc-pack-actions {
            width: 100%;
        }

        .btn-pdf,
        .btn-review {
            flex: 1;
        }
    }
</style>
<?php

/*
|--------------------------------------------------------------------------
| PROCUREMENT SUMMARY
|--------------------------------------------------------------------------
*/

$getSummary = mysqli_query($conn, "

    SELECT

        COALESCE(SUM(item_total),0) AS total_po_amount

    FROM purchase_request_items

");

$summary = mysqli_fetch_assoc($getSummary);

$totalPO = $summary['total_po_amount'];

/*
|--------------------------------------------------------------------------
| INVOICE DUE (50%)
|--------------------------------------------------------------------------
*/

$invoiceDue = $totalPO * 0.50;

/*
|--------------------------------------------------------------------------
| RETENTION (10%)
|--------------------------------------------------------------------------
*/

$retention = $invoiceDue * 0.10;

/*
|--------------------------------------------------------------------------
| APPROVED VS TOTAL
|--------------------------------------------------------------------------
*/

$getApproved = mysqli_query($conn, "

    SELECT COUNT(*) AS approved_count

    FROM purchase_requests

    WHERE status = 'Approved'

");

$approved = mysqli_fetch_assoc($getApproved);

$getTotalRequests = mysqli_query($conn, "

    SELECT COUNT(*) AS total_requests

    FROM purchase_requests

");

$totalRequests = mysqli_fetch_assoc($getTotalRequests);

$progressPercent = 0;

if ($totalRequests['total_requests'] > 0) {

    $progressPercent = (
        $approved['approved_count']
        /
        $totalRequests['total_requests']
    ) * 100;
}

?>

<div class="doc-pack-header">

    <div>

        <h1>Construction Document Pack</h1>

        <p>

            Premium sample templates for procurement,
            site delivery, invoice billing,
            and progress billing.

        </p>

    </div>

    <div class="doc-pack-actions">

        <button class="btn-pdf">

            <i class="bi bi-printer"></i>

            Print / Save as PDF

        </button>

        <button class="btn-review">

            <i class="bi bi-clipboard-check"></i>

            Start Review

        </button>

    </div>

</div>

<div class="doc-stats">

    <!-- TOTAL PO -->

    <div class="doc-stat-card">

        <span>TOTAL PO AMOUNT</span>

        <h2>

            ₱<?php echo number_format(
                    $totalPO,
                    2
                ); ?>

        </h2>

        <p>

            Combined procurement request value

        </p>

    </div>

    <!-- INVOICE DUE -->

    <div class="doc-stat-card">

        <span>INVOICE DUE</span>

        <h2>

            ₱<?php echo number_format(
                    $invoiceDue,
                    2
                ); ?>

        </h2>

        <p>

            Estimated 50% billing schedule

        </p>

    </div>

    <!-- PROGRESS -->

    <div class="doc-stat-card">

        <span>PROGRESS CLAIM</span>

        <h2>

            <?php echo number_format(
                $progressPercent,
                0
            ); ?>%

        </h2>

        <p>

            Approved procurement completion

        </p>

    </div>

    <!-- RETENTION -->

    <div class="doc-stat-card">

        <span>RETENTION</span>

        <h2>

            ₱<?php echo number_format(
                    $retention,
                    2
                ); ?>

        </h2>

        <p>

            10% retained payment value

        </p>

    </div>

</div>
<?php

$getPO = mysqli_query($conn, "
    SELECT
        purchase_requests.*,
        contractors.name AS contractor_name,
        contractors.location AS contractor_location,
        contractors.contact_person,
        contractors.contact
    FROM purchase_requests
    LEFT JOIN contractors
    ON contractors.id = purchase_requests.contractor_id
    WHERE purchase_requests.status = 'Approved'
    ORDER BY purchase_requests.id DESC

");
if (mysqli_num_rows($getPO) == 0) {
?>

    <div class="empty-po-state">

        <div class="empty-po-icon">
            <i class="bi bi-file-earmark-x"></i>
        </div>

        <h2>No Approved Purchase Requests</h2>

        <p>
            Purchase orders will automatically appear here once
            a purchase request status becomes approved.
        </p>

    </div>

<?php

}
while ($po = mysqli_fetch_assoc($getPO)) {

?>

    <div class="po-wrapper mb-4">
        <!-- HEADER -->
        <div class="po-header">
            <div class="po-company">
                <div class="po-logo">
                    <?php
                    echo strtoupper(
                        substr($po['contractor_name'], 0, 3)
                    );
                    ?>
                </div>
                <div>
                    <h2>
                        <?php echo $po['contractor_name']; ?>
                    </h2>
                    <p>
                        <?php echo $po['contractor_location']; ?>
                    </p>
                    <p>
                        Contact Person:
                        <?php echo $po['contact_person']; ?>
                    </p>
                    <p>
                        Mobile:
                        <?php echo $po['contact']; ?>
                    </p>
                </div>
            </div>
            <div class="po-title">
                <h1>PURCHASE ORDER</h1>
                <span>
                    APPROVED FOR SUPPLIER FULFILLMENT
                </span>
                <p>
                    <b>PO No.:</b>
                    PO-<?php echo str_pad(
                            $po['id'],
                            5,
                            '0',
                            STR_PAD_LEFT
                        ); ?>
                </p>
                <p>
                    <b>Date:</b>
                    <?php
                    echo date(
                        'F d, Y',
                        strtotime($po['date_needed'])
                    );
                    ?>
                </p>
                <p>
                    <b>Project:</b>
                    <?php echo $po['request_title']; ?>
                </p>
            </div>
        </div>
        <!-- TOP INFO -->
        <div class="po-info-grid">
            <div class="po-card">
                <h4>SUPPLIER</h4>
                <strong>
                    <?php echo $po['contractor_name']; ?>
                </strong>
                <p>
                    <?php echo $po['contractor_location']; ?>
                </p>
                <p>
                    Contact Person:
                    <?php echo $po['contact_person']; ?>
                </p>
                <p>
                    Mobile:
                    <?php echo $po['contact']; ?>
                </p>
            </div>
            <div class="po-card">
                <h4>REQUEST INFORMATION</h4>
                <strong>
                    <?php echo $po['request_title']; ?>
                </strong>
                <p>
                    Department:
                    <?php echo $po['department']; ?>
                </p>
                <p>
                    Requested by:
                    <?php echo $po['requested_by']; ?>
                </p>
                <p>
                    Date Needed:
                    <?php
                    echo date(
                        'F d, Y',
                        strtotime($po['date_needed'])
                    );
                    ?>
                </p>
            </div>
        </div>
        <!-- TABLE -->
        <div class="table-responsive">
            <table class="table po-table">
                <thead>
                    <tr>
                        <th>ITEM</th>
                        <th>DESCRIPTION</th>
                        <th>SPECS</th>
                        <th class="text-end">QTY</th>
                        <th>UNIT</th>
                        <th class="text-end">UNIT PRICE</th>
                        <th class="text-end">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    $subtotal = 0;
                    $getItems = mysqli_query($conn, "
                    SELECT *
                    FROM purchase_request_items
                    WHERE request_id = '" . $po['id'] . "'
                ");
                    while ($item = mysqli_fetch_assoc($getItems)) {
                        $subtotal += $item['item_total'];
                    ?>
                        <tr>
                            <td>
                                <?php echo $counter++; ?>
                            </td>
                            <td>
                                <?php echo $item['item_name']; ?>
                            </td>
                            <td>
                                <?php echo $item['specifications']; ?>
                            </td>
                            <td class="text-end">
                                <?php echo $item['quantity']; ?>
                            </td>
                            <td>
                                <?php echo $item['unit']; ?>
                            </td>
                            <td class="text-end">
                                ₱<?php echo number_format(
                                        $item['unit_price'],
                                        2
                                    ); ?>
                            </td>
                            <td class="text-end">
                                ₱<?php echo number_format(
                                        $item['item_total'],
                                        2
                                    ); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php
        $grandTotal = $subtotal;
        ?>
        <?php if (!empty($po['approval_remarks'])) { ?>
            <div style="
        margin:18px 28px 0;
        padding:18px 20px;
        border-radius:18px;
        background:#FFF;
        border:1px solid #082c59;
    ">
                <div style="
            font-size:11px;
            font-weight:800;
            letter-spacing:.8px;
            color:#082c59;
            margin-bottom:10px;
        ">
                    CEO / MANAGEMENT REMARKS
                </div>

                <div style="
            font-size:13px;
            line-height:1.7;
            color: #66788d;
            font-weight:500;
        ">
                    <?php echo nl2br($po['approval_remarks']); ?>
                </div>
            </div>
        <?php } ?>
        <!-- BOTTOM -->
        <div class="po-bottom">
            <!-- TERMS -->
            <div class="po-terms">
                <h5>Terms and Conditions</h5>
                <p>
                    Supplier shall deliver only approved brands and specifications.
                    Shortages, damaged items, or rejected materials shall be replaced
                    within 3 working days. Delivery receipt, sales invoice,
                    and inspection acceptance are required prior to payment processing.
                </p>
            </div>
            <!-- TOTALS -->
            <div class="po-summary">
                <div class="sum-row">
                    <span>Subtotal</span>
                    <strong>
                        ₱<?php echo number_format(
                                $subtotal,
                                2
                            ); ?>
                    </strong>
                </div>
                <div class="sum-row">
                    <span>Status</span>
                    <strong>
                        <?php echo $po['status']; ?>
                    </strong>
                </div>
                <div class="sum-row">
                    <span>Contractor</span>
                    <strong>
                        <?php echo $po['contractor_name']; ?>
                    </strong>
                </div>
                <div class="sum-total">
                    <span>Total PO Amount</span>
                    <strong>
                        ₱<?php echo number_format(
                                $grandTotal,
                                2
                            ); ?>
                    </strong>
                </div>
            </div>
        </div>
        <!-- SIGNATURE -->
        <div class="po-signatures">
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>Prepared by</strong>
                <p>
                    Procurement Officer
                </p>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>Reviewed by</strong>
                <p>
                    Project Manager
                </p>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <strong>Approved by</strong>
                <p>
                    Authorized Signatory
                </p>
            </div>
        </div>

    </div>

<?php } ?>