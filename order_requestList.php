<?php
$role = $_SESSION['role'] ?? '';
$canApprovePurchaseRequests = in_array($role, ['ceo', 'system_admin'], true);

if (isset($_POST['update_status'])) {
    if (!$canApprovePurchaseRequests) {
        echo "<div class='alert alert-danger'>Only the owner can approve or reject purchase requests.</div>";
    } else {
        $request_id = $_POST['request_id'];
        $status = $_POST['status'];
        $approval_remarks = mysqli_real_escape_string(
            $conn,
            $_POST['approval_remarks']
        );
        $allowed_statuses = [
            'Requested',
            'Approved',
            'Procurement',
            'Rejected'
        ];
        if (in_array($status, $allowed_statuses)) {
            $stmt = $conn->prepare("
                UPDATE purchase_requests
                SET
                    status = ?,
                    approval_remarks = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "ssi",
                $status,
                $approval_remarks,
                $request_id
            );
            $stmt->execute();
            $stmt->close();
        }
    }
}
$stmt = $conn->prepare("
    SELECT
        purchase_requests.*,
        contractors.name AS contractor_name
    FROM purchase_requests
    LEFT JOIN contractors
    ON contractors.id = purchase_requests.contractor_id
    ORDER BY purchase_requests.id DESC
");
$stmt->execute();
$getRequests = $stmt->get_result();
$allModals = '';
?>
<style>
    .approval-wrapper {
        padding: 4px;
    }

    .approval-header {
        margin-bottom: 24px;
    }

    .approval-header h1 {
        margin: 0 0 8px;
        font-size: 28px;
        font-weight: 800;
        color: #FFF;
    }

    .approval-header p {
        margin: 0;
        font-size: 14px;
        color: #61738d;
    }

    .approval-card {
        background: #fff;
        border: 1px solid #dce5f1;
        border-radius: 28px;
        overflow: hidden;
    }

    .approval-card .table-responsive {
        overflow-x: auto;
        overflow-y: visible !important;
    }

    .approval-table tbody td {
        overflow: visible !important;
    }

    .approval-table {
        width: 100% !important;
        margin: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0;
    }

    .approval-table thead th {
        background: #082c59 !important;
        color: #fff !important;
        border: none !important;
        padding: 16px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .approval-table tbody td {
        padding: 16px;
        border-color: #e5edf7;
        font-size: 13px;
        font-weight: 500;
        color: #203554;
        vertical-align: middle;
        white-space: nowrap;
    }

    .approval-table tbody tr:hover td {
        background: #f8fbff;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 34px;
        padding: 0 14px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
    }

    .status-requested {
        background: #eef4fb;
        color: #21558c;
    }

    .status-approved {
        background: #edf8f1;
        color: #11724d;
    }

    .status-procurement {
        background: #fff5e8;
        color: #c17a00;
    }

    .status-rejected {
        background: #ffecec;
        color: #b42318;
    }

    .btn-disabled {
        background: #eef2f7;
        color: #64748b;
        cursor: not-allowed;
    }

    .action-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-action {
        height: 38px;
        padding: 0 14px;
        border: none;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
    }

    .btn-view {
        background: #123866;
        color: #fff;
    }

    .btn-approved {
        background: #eaf7ef;
        color: #11724d;
    }

    .btn-procurement {
        background: #fff4e5;
        color: #b26b00;
    }

    .btn-reject {
        background: #ffecec;
        color: #b42318;
    }

    .modal-content {
        border: none;
        border-radius: 30px;
    }

    .modal-header {
        padding: 24px 28px;
        border-bottom: 1px solid #e4ebf5;
    }

    .modal-body {
        padding: 28px;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
        margin-bottom: 20px;
    }

    .detail-card {
        padding: 18px;
        border-radius: 20px;
        border: 1px solid #dce5f1;
        background: #f8fbff;
    }

    .detail-card h6 {
        margin: 0 0 10px;
        font-size: 11px;
        font-weight: 800;
        color: #587292;
        letter-spacing: .7px;
    }

    .detail-card strong {
        display: block;
        font-size: 15px;
        font-weight: 700;
        color: #102544;
    }

    .detail-card p {
        margin: 8px 0 0;
        font-size: 13px;
        line-height: 1.7;
        color: #5f7189;
    }

    .items-table thead th {
        background: #123866 !important;
        color: #fff !important;
        border: none !important;
        padding: 12px;
        font-size: 11px;
        font-weight: 800;
    }

    .items-table tbody td {
        padding: 12px;
        font-size: 12px;
    }

    .grand-total {
        margin-top: 20px;
        padding: 20px;
        border-radius: 20px;
        background: #08244c;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .grand-total strong {
        font-size: 24px;
        font-weight: 800;
    }

    .approval-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .action-popup {
        position: relative;
    }

    .approval-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: nowrap;
    }

    .btn-icon-action {
        width: 44px;
        height: 44px;
        border: none;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        transition: .18s ease;
    }

    .btn-icon-action:hover {
        transform: translateY(-2px);
    }

    .btn-approve-icon {
        background: #edf8f1;
        color: #11724d;
    }

    .btn-procurement-icon {
        background: #fff4e8;
        color: #bc7400;
    }

    .btn-reject-icon {
        background: #ffecec;
        color: #b42318;
    }

    .action-popup-form {
        position: absolute;
        top: 56px;
        right: 0;
        width: 300px;
        padding: 18px;
        border-radius: 22px;
        background: #fff;
        border: 1px solid #dbe5f1;
        box-shadow:
            0 20px 40px rgba(15, 35, 66, .14),
            0 2px 10px rgba(15, 35, 66, .06);
        z-index: 9999;
        display: none;
        height: 235px;
    }

    .action-popup-form.active {
        display: block;
        animation: popupFade .18s ease;
    }

    .action-popup-form h6 {
        margin: 0 0 14px;
        font-size: 14px;
        font-weight: 800;
        color: #102544;
    }

    .action-popup-form textarea {
        width: 100%;
        min-height: 110px;
        border: 1px solid #d8e2ef;
        border-radius: 18px;
        padding: 14px;
        resize: none;
        font-size: 13px;
        line-height: 1.6;
        color: #203554;
        outline: none;
        transition: .18s ease;
        margin-bottom: 14px;
    }

    .action-popup-form textarea:focus {
        border-color: #8fb3e3;
        box-shadow: 0 0 0 4px rgba(18, 56, 102, .08);
    }

    .popup-submit {
        width: 100%;
        height: 46px;
        max-width: 262px;
        border: none;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 700;
        transition: .18s ease;
        position: absolute;
        left: 18px;
        bottom: 18px;
    }

    .popup-submit:hover {
        transform: translateY(-1px);
    }

    .popup-approve {
        background: #123866;
        color: #fff;
    }

    .popup-procurement {
        background: #d98b00;
        color: #fff;
    }

    .popup-reject {
        background: #c62828;
        color: #fff;
    }

    @keyframes popupFade {
        from {
            opacity: 0;
            transform: translateY(-6px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media(max-width:991px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }

        .action-group {
            flex-direction: column;
        }
    }
</style>
<div class="approval-wrapper">
    <div class="approval-header">
        <h1>Purchase Request Approval</h1>
        <p>
            Review submitted procurement requests. Owner approval is required before procurement can proceed.
        </p>
    </div>
    <div class="approval-card">
        <div class="table-responsive">
            <table class="table approval-table">
                <thead>
                    <tr>
                        <th>REQUEST NO</th>
                        <th>REQUEST TITLE</th>
                        <th>DEPARTMENT</th>
                        <th>REQUESTED BY</th>
                        <th>DATE NEEDED</th>
                        <th>STATUS</th>
                        <th style="min-width: 350px;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($request = mysqli_fetch_assoc($getRequests)) {
                        $statusClass = 'status-requested';
                        if ($request['status'] == 'Approved') {
                            $statusClass = 'status-approved';
                        }
                        if ($request['status'] == 'Procurement') {
                            $statusClass = 'status-procurement';
                        }
                        if ($request['status'] == 'Rejected') {
                            $statusClass = 'status-rejected';
                        }
                    ?>
                        <tr>
                            <td>
                                <?php echo $request['request_no']; ?>
                            </td>
                            <td>
                                <?php echo $request['request_title']; ?>
                            </td>
                            <td>
                                <?php echo $request['department']; ?>
                            </td>
                            <td>
                                <?php echo $request['requested_by']; ?>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($request['date_needed'])); ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo $request['status']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="approval-actions">

                                    <button
                                        class="btn-action btn-view"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewModal<?php echo $request['id']; ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <?php if ($canApprovePurchaseRequests): ?>

                                        <!-- APPROVE -->
                                        <div class="action-popup">

                                            <button
                                                type="button"
                                                class="btn-icon-action btn-approve-icon"
                                                onclick="toggleApprovalPopup('approve-<?php echo $request['id']; ?>')">
                                                <i class="bi bi-check-lg"></i>
                                            </button>

                                            <form
                                                method="POST"
                                                class="action-popup-form"
                                                id="approve-<?php echo $request['id']; ?>">

                                                <h6>Approval Remarks</h6>

                                                <input
                                                    type="hidden"
                                                    name="request_id"
                                                    value="<?php echo $request['id']; ?>">

                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="Approved">

                                                <textarea
                                                    name="approval_remarks"
                                                    placeholder="Enter approval remarks..."
                                                    required></textarea>

                                                <button
                                                    type="submit"
                                                    name="update_status"
                                                    class="popup-submit popup-approve">
                                                    Approve Request
                                                </button>

                                            </form>

                                        </div>

                                        <!-- PROCUREMENT -->
                                        <div class="action-popup">

                                            <button
                                                type="button"
                                                class="btn-icon-action btn-procurement-icon"
                                                onclick="toggleApprovalPopup('procurement-<?php echo $request['id']; ?>')">
                                                <i class="bi bi-box-seam"></i>
                                            </button>

                                            <form
                                                method="POST"
                                                class="action-popup-form"
                                                id="procurement-<?php echo $request['id']; ?>">

                                                <h6>Procurement Remarks</h6>

                                                <input
                                                    type="hidden"
                                                    name="request_id"
                                                    value="<?php echo $request['id']; ?>">

                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="Procurement">

                                                <textarea
                                                    name="approval_remarks"
                                                    placeholder="Enter procurement remarks..."
                                                    required></textarea>

                                                <button
                                                    type="submit"
                                                    name="update_status"
                                                    class="popup-submit popup-procurement">
                                                    Send to Procurement
                                                </button>

                                            </form>

                                        </div>

                                        <!-- REJECT -->
                                        <div class="action-popup">

                                            <button
                                                type="button"
                                                class="btn-icon-action btn-reject-icon"
                                                onclick="toggleApprovalPopup('reject-<?php echo $request['id']; ?>')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>

                                            <form
                                                method="POST"
                                                class="action-popup-form"
                                                id="reject-<?php echo $request['id']; ?>">

                                                <h6>Reason for Rejection</h6>

                                                <input
                                                    type="hidden"
                                                    name="request_id"
                                                    value="<?php echo $request['id']; ?>">

                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="Rejected">

                                                <textarea
                                                    name="approval_remarks"
                                                    placeholder="Enter rejection reason..."
                                                    required></textarea>

                                                <button
                                                    type="submit"
                                                    name="update_status"
                                                    class="popup-submit popup-reject">
                                                    Reject Request
                                                </button>

                                            </form>

                                        </div>

                                    <?php else: ?>

                                        <button
                                            type="button"
                                            class="btn-action btn-disabled"
                                            disabled>
                                            Owner approval required
                                        </button>

                                    <?php endif; ?>

                                </div>
                            </td>
                        </tr>
                        <?php
                        ob_start();
                        ?>
                        <div
                            class="modal fade"
                            id="viewModal<?php echo $request['id']; ?>"
                            tabindex="-1">
                            <div class="modal-dialog modal-xl modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <div>
                                            <h5>
                                                <?php echo $request['request_title']; ?>
                                            </h5>
                                            <small>
                                                <?php echo $request['request_no']; ?>
                                            </small>
                                        </div>
                                        <button
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="detail-grid">
                                            <div class="detail-card">
                                                <h6>REQUEST INFORMATION</h6>
                                                <strong>
                                                    <?php echo $request['department']; ?>
                                                </strong>
                                                <p>
                                                    Requested by:
                                                    <?php echo $request['requested_by']; ?>
                                                    <br><br>
                                                    Contractor:
                                                    <?php echo $request['contractor_name']; ?>
                                                </p>
                                            </div>
                                            <div class="detail-card">
                                                <h6>JUSTIFICATION</h6>
                                                <p>
                                                    <?php echo nl2br($request['justification']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table items-table">
                                                <thead>
                                                    <tr>
                                                        <th>ITEM</th>
                                                        <th>SPECIFICATIONS</th>
                                                        <th>QTY</th>
                                                        <th>UNIT</th>
                                                        <th>UNIT PRICE</th>
                                                        <th>TOTAL</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $grandTotal = 0;
                                                    $itemStmt = $conn->prepare("
                                                    SELECT *
                                                    FROM purchase_request_items
                                                    WHERE request_id = ?
                                                ");
                                                    $itemStmt->bind_param(
                                                        "i",
                                                        $request['id']
                                                    );
                                                    $itemStmt->execute();
                                                    $getItems = $itemStmt->get_result();
                                                    while ($item = mysqli_fetch_assoc($getItems)) {
                                                        $grandTotal += $item['item_total'];
                                                    ?>
                                                        <tr>
                                                            <td>
                                                                <?php echo $item['item_name']; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo $item['specifications']; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo $item['quantity']; ?>
                                                            </td>
                                                            <td>
                                                                <?php echo $item['unit']; ?>
                                                            </td>
                                                            <td>
                                                                ₱<?php echo number_format($item['unit_price'], 2); ?>
                                                            </td>
                                                            <td>
                                                                ₱<?php echo number_format($item['item_total'], 2); ?>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="grand-total">
                                            <span>GRAND TOTAL</span>
                                            <strong>
                                                ₱<?php echo number_format($grandTotal, 2); ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        $allModals .= ob_get_clean();
                        ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    function toggleApprovalPopup(id) {

        document
            .querySelectorAll('.action-popup-form')
            .forEach(el => {
                if (el.id !== id) {
                    el.classList.remove('active');
                }
            });

        document
            .getElementById(id)
            .classList.toggle('active');
    }

    document.addEventListener('click', function(e) {

        if (!e.target.closest('.action-popup')) {

            document
                .querySelectorAll('.action-popup-form')
                .forEach(el => {
                    el.classList.remove('active');
                });
        }
    });
</script>
<?php
echo $allModals;
$stmt->close();
?>