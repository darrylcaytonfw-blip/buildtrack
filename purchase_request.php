<?php
include 'database.php';
$success = '';
/*
|--------------------------------------------------------------------------
| SAVE REQUEST
|--------------------------------------------------------------------------
*/
if (isset($_POST['submit_request'])) {
    $request_no = 'ORF-' . date('YmdHis');
    $request_title = mysqli_real_escape_string(
        $conn,
        $_POST['request_title']
    );
    $priority_level = mysqli_real_escape_string(
        $conn,
        $_POST['priority_level']
    );
    $department = mysqli_real_escape_string(
        $conn,
        $_POST['department']
    );
    $date_needed = mysqli_real_escape_string(
        $conn,
        $_POST['date_needed']
    );
    $requested_by = mysqli_real_escape_string(
        $conn,
        $_POST['requested_by']
    );
    $cost_code = mysqli_real_escape_string(
        $conn,
        $_POST['cost_code']
    );
    $contractor_id = mysqli_real_escape_string(
        $conn,
        $_POST['contractor_id']
    );
    $justification = mysqli_real_escape_string(
        $conn,
        $_POST['justification']
    );
    mysqli_query($conn, "
        INSERT INTO purchase_requests (
            request_no,
            request_title,
            priority_level,
            department,
            date_needed,
            requested_by,
            cost_code,
            contractor_id,
            justification
        )
        VALUES (
            '$request_no',
            '$request_title',
            '$priority_level',
            '$department',
            '$date_needed',
            '$requested_by',
            '$cost_code',
            '$contractor_id',
            '$justification'
        )
    ");
    $request_id = mysqli_insert_id($conn);
    if (isset($_POST['item_name'])) {
        foreach ($_POST['item_name'] as $key => $item) {
            $item_name = mysqli_real_escape_string(
                $conn,
                $item
            );
            $specifications = mysqli_real_escape_string(
                $conn,
                $_POST['specifications'][$key]
            );
            $quantity = mysqli_real_escape_string(
                $conn,
                $_POST['quantity'][$key]
            );
            $unit = mysqli_real_escape_string(
                $conn,
                $_POST['unit'][$key]
            );
            $remarks = mysqli_real_escape_string(
                $conn,
                $_POST['remarks'][$key]
            );
            $unit_price = mysqli_real_escape_string(
                $conn,
                $_POST['unit_price'][$key]
            );
            $item_total = (
                floatval($quantity)
                *
                floatval($unit_price)
            );
            mysqli_query($conn, "
                INSERT INTO purchase_request_items (
                    request_id,
                    item_name,
                    specifications,
                    quantity,
                    unit,
                    unit_price,
                    item_total,
                    remarks
                )
                VALUES (
                    '$request_id',
                    '$item_name',
                    '$specifications',
                    '$quantity',
                    '$unit',
                    '$unit_price',
                    '$item_total',
                    '$remarks'
                )
            ");
        }
    }
    $success = 'Purchase request submitted successfully.';
}
?>
<style>
    .request-form-wrap {
        margin-bottom: 28px;
        padding: 28px;
        background: #f7f9fc;
        border: 1px solid #d9e2ef;
        border-radius: 30px;
        box-shadow: 0 8px 24px rgba(15, 35, 66, .04);
    }

    .request-form-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 26px;
    }

    .request-form-header h2 {
        margin: 0 0 6px;
        font-size: 26px;
        font-weight: 800;
        color: #0f2342;
        letter-spacing: -.4px;
    }

    .request-form-header p {
        margin: 0;
        font-size: 13px;
        font-weight: 500;
        color: #6d7f98;
    }

    .request-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .request-field label {
        display: block;
        margin-bottom: 9px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .3px;
        color: #102544;
    }

    .request-field input,
    .request-field select,
    .request-field textarea {
        width: 100%;
        border: 1px solid #d8e2ef;
        background: #fff;
        border-radius: 18px;
        padding: 15px 16px;
        font-size: 13px;
        font-weight: 500;
        color: #203554;
        outline: none;
        transition: .2s ease;
    }

    .request-field input:focus,
    .request-field select:focus,
    .request-field textarea:focus {
        border-color: #8fb3e3;
        box-shadow: 0 0 0 4px rgba(18, 56, 102, .08);
    }

    .request-field textarea {
        resize: none;
        min-height: 120px;
    }

    /* ITEMS */
    .items-section {
        margin-top: 28px;
        padding-top: 24px;
        border-top: 1px solid #e4ebf5;
    }

    .items-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
    }

    .items-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 800;
        color: #102544;
    }

    .btn-add-item {
        height: 42px;
        padding: 0 18px;
        border: none;
        border-radius: 14px;
        background: #123866;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        transition: .2s ease;
    }

    .btn-add-item:hover {
        background: #0f2f56;
    }

    .item-row {
        display: grid;
        grid-template-columns: 1.2fr 1.3fr .6fr .7fr .8fr 1fr;
        gap: 12px;
        margin-bottom: 12px;
    }

    .item-row input {
        width: 100%;
        border: 1px solid #d8e2ef;
        background: #fff;
        border-radius: 16px;
        padding: 14px 15px;
        font-size: 13px;
        font-weight: 500;
        outline: none;
        transition: .2s ease;
    }

    .item-row input:focus {
        border-color: #8fb3e3;
        box-shadow: 0 0 0 4px rgba(18, 56, 102, .08);
    }

    .btn-submit-request {
        height: 50px;
        padding: 0 26px;
        border: none;
        border-radius: 16px;
        background: #08244c;
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        transition: .2s ease;
    }

    .btn-submit-request:hover {
        background: #061b39;
    }

    /* ORDER REQUEST */
    .or-wrapper {
        background: #f5f7fb;
        border: 1px solid #d8e1ef;
        border-radius: 32px;
        overflow: hidden;
        color: #12284a;
        font-family: Inter, sans-serif;
        box-shadow: 0 8px 28px rgba(15, 35, 66, .05);
    }

    .or-header {
        display: flex;
        justify-content: space-between;
        gap: 30px;
        padding: 30px 34px;
        background: #f3f6fb;
        border-bottom: 1px solid #d8e1ef;
    }

    .or-company {
        display: flex;
        gap: 18px;
    }

    .or-logo {
        width: 72px;
        height: 72px;
        border-radius: 22px;
        background: #08244c;
        color: #fff;
        font-size: 20px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .or-company h2 {
        margin: 0 0 8px;
        font-size: 19px;
        font-weight: 800;
        color: #102544;
    }

    .or-company p {
        margin: 4px 0;
        font-size: 13px;
        font-weight: 500;
        color: #697c97;
    }

    .or-title {
        text-align: right;
    }

    .or-title h1 {
        margin: 0;
        font-size: 32px;
        font-weight: 900;
        letter-spacing: 1px;
        color: #08244c;
    }

    .or-title span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 12px 0 14px;
        padding: 9px 18px;
        border-radius: 999px;
        border: 1px solid #c9d9ef;
        background: #eaf2fb;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1px;
        color: #18508b;
    }

    .or-title p {
        margin: 4px 0;
        font-size: 13px;
        font-weight: 500;
        color: #667891;
    }

    .or-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        padding: 32px 34px 16px;
    }

    .or-card {
        background: #f7f9fc;
        border: 1px solid #d9e2ef;
        border-radius: 24px;
        padding: 20px;
    }

    .or-card h4 {
        margin: 0 0 14px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .8px;
        color: #08244c;
    }

    .or-card strong {
        display: block;
        margin-bottom: 8px;
        font-size: 15px;
        font-weight: 700;
        color: #102544;
    }

    .or-card p {
        margin: 4px 0;
        font-size: 13px;
        line-height: 1.8;
        color: #52647d;
    }

    /* TABLE */
    .or-table {
        margin: 12px 34px 0;
        border-radius: 20px;
        overflow: hidden;
        background: #fff;
    }

    .or-table thead th {
        background: #123866 !important;
        color: #fff !important;
        border: none !important;
        padding: 15px 14px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .7px;
        white-space: nowrap;
    }

    .or-table tbody td {
        background: #fff;
        border-color: #dde5f0;
        padding: 15px 14px;
        font-size: 13px;
        font-weight: 500;
        color: #213554;
        vertical-align: middle;
    }

    .or-table tbody tr:hover td {
        background: #f8fbff;
    }

    /* FLOW */
    .or-flow {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 14px;
        padding: 24px 34px 12px;
    }

    .flow-card {
        padding: 18px;
        border-radius: 22px;
        border: 1px solid #d7e0ed;
        background: #f8fafc;
    }

    .flow-card strong {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 800;
        color: #102544;
    }

    .flow-card span {
        font-size: 12px;
        line-height: 1.6;
        color: #52647e;
    }

    .flow-card.active {
        background: #edf7f2;
        border-color: #add9c7;
    }

    .flow-card.processing {
        background: #fff8ef;
        border-color: #f0c58c;
    }

    /* SIGNATURE */
    .or-signatures {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
        padding: 28px 34px 34px;
    }

    .or-sign-box strong {
        display: block;
        margin-top: 10px;
        font-size: 13px;
        font-weight: 700;
        color: #102544;
    }

    .or-sign-box p {
        margin: 4px 0 0;
        font-size: 12px;
        font-weight: 500;
        color: #6c7d96;
    }

    .sig-line {
        height: 1px;
        background: #8a9ab3;
    }

    /* ALERT */
    .alert-success {
        border: none;
        border-radius: 18px;
        padding: 16px 18px;
        background: #eaf8f0;
        color: #0f6b45;
        font-size: 13px;
        font-weight: 700;
    }

    .contractor-badge {
        margin-top: 16px;
        display: inline-flex;
        flex-direction: column;
        gap: 4px;
        padding: 10px 14px;
        border-radius: 16px;
        background: #edf4ff;
        border: 1px solid #cfe0fb;
    }

    .contractor-badge span {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .8px;
        text-transform: uppercase;
        color: #3d6ea8;
    }

    .contractor-badge strong {
        font-size: 13px;
        font-weight: 700;
        color: #102544;
    }

    /* MOBILE */
    @media(max-width:991px) {

        .request-grid,
        .or-info-grid,
        .or-flow,
        .or-signatures,
        .item-row {
            grid-template-columns: 1fr;
        }

        .or-header,
        .request-form-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .or-title {
            text-align: left;
        }

        .or-title h1 {
            font-size: 26px;
        }

        .btn-submit-request {
            width: 100%;
        }

        .items-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .btn-add-item {
            width: 100%;
        }
    }
</style>
<?php if (!$isManagement): ?>
    <!-- FORM -->
    <div class="request-form-wrap">
        <div class="request-form-header">
            <div>
                <h2>Create Purchase Request</h2>
                <p>
                    Submit procurement materials and site requirement requests.
                </p>
            </div>
        </div>
        <?php if ($success) { ?>
            <div class="alert alert-success mb-4">
                <?php echo $success; ?>
            </div>
        <?php } ?>
        <form method="POST">
            <div class="request-grid">
                <div class="request-field">
                    <label>Request Title</label>
                    <input type="text" name="request_title" required>
                </div>
                <div class="request-field">
                    <label>Priority Level</label>
                    <select name="priority_level" required>
                        <option value="">Select Priority</option>
                        <option value="High">High</option>
                        <option value="Normal">Normal</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
                <div class="request-field">
                    <label>Department</label>
                    <input type="text" name="department" required>
                </div>
                <div class="request-field">
                    <label>Date Needed</label>
                    <input type="date" name="date_needed" required>
                </div>
                <div class="request-field">
                    <label>Requested By</label>
                    <input type="text" name="requested_by" required>
                </div>
                <div class="request-field">
                    <label>Cost Code</label>
                    <input type="text" name="cost_code" required>
                </div>
                <div class="request-field">
                    <label>Select Contractor</label>
                    <select name="contractor_id" required>
                        <option value="">
                            Select Contractor
                        </option>
                        <?php
                        $getContractors = mysqli_query($conn, "
            SELECT *
            FROM contractors
            ORDER BY name ASC
        ");
                        while ($contractor = mysqli_fetch_assoc($getContractors)) {
                        ?>
                            <option value="<?php echo $contractor['id']; ?>">
                                <?php echo $contractor['name']; ?>
                                —
                                <?php echo $contractor['location']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="request-field mt-3">
                <label>Purpose / Justification</label>
                <textarea rows="4" name="justification" required></textarea>
            </div>
            <!-- ITEMS -->
            <div class="items-section">
                <div class="items-header">
                    <h3>Requested Items</h3>
                    <button type="button" class="btn-add-item" onclick="addItemRow()">
                        Add Item
                    </button>
                </div>
                <div id="items-container">
                    <div class="item-row">
                        <input type="text" name="item_name[]" placeholder="Item Name" required>
                        <input type="text" name="specifications[]" placeholder="Specifications">
                        <input type="number" name="quantity[]" placeholder="Quantity" step="0.01">
                        <select name="unit[]" required>
                            <option value="">
                                Unit
                            </option>
                            <?php
                            $getUnits = mysqli_query($conn, "
        SELECT *
        FROM units
        ORDER BY unit_name ASC
    ");
                            while ($unit = mysqli_fetch_assoc($getUnits)) {
                            ?>
                                <option value="<?php echo $unit['unit_name']; ?>">
                                    <?php echo $unit['unit_name']; ?>
                                </option>
                            <?php } ?>
                        </select>
                        <input type="number" step="0.01" name="unit_price[]" placeholder="Unit Price">
                        <input type="text" name="remarks[]" placeholder="Remarks">
                    </div>
                </div>
            </div>
            <button type="submit" name="submit_request" class="btn-submit-request mt-4">
                Submit Request
            </button>
        </form>
    </div>
<?php endif; ?>
<?php
$getRequest = mysqli_query($conn, "
                         SELECT
        purchase_requests.*,
        contractors.name AS contractor_name,
        contractors.location AS contractor_location,
        contractors.contact_person,
        contractors.contact
    FROM purchase_requests
    LEFT JOIN contractors
    ON contractors.id = purchase_requests.contractor_id
    ORDER BY purchase_requests.id DESC

");
while ($request = mysqli_fetch_assoc($getRequest)) {
?>
    <div class="or-wrapper mb-4">
        <!-- HEADER -->
        <div class="or-header">
            <div class="or-company">
                <div class="or-logo">
                    <?php
                    echo strtoupper(
                        substr($request['contractor_name'], 0, 3)
                    );
                    ?>
                </div>
                <div>
                    <h2>
                        <?php echo $request['contractor_name']; ?>
                    </h2>
                    <p style="line-height: 1.2;">
                        <?php echo $request['contractor_location']; ?><br>
                        Contact Person:
                        <?php echo $request['contact_person']; ?><br>
                        Mobile:
                        <?php echo $request['contact']; ?>
                    </p>
                </div>
            </div>
            <div class="or-title">
                <h1>ORDER REQUEST</h1>
                <span>FOR PROCUREMENT PROCESSING</span>
                <p>
                    <b>Request No.:</b>
                    <?php echo $request['request_no']; ?>
                </p>
                <p>
                    <b>Date Needed:</b>
                    <?php
                    echo date(
                        'F d, Y',
                        strtotime($request['date_needed'])
                    );
                    ?>
                </p>
            </div>
        </div>
        <!-- INFO -->
        <div class="or-info-grid">
            <div class="or-card">
                <h4>REQUESTING DEPARTMENT</h4>
                <strong>
                    <?php echo $request['department']; ?>
                </strong>
                <p>
                    Requested by:
                    <?php echo $request['requested_by']; ?>
                </p>
                <p>
                    Cost Code:
                    <?php echo $request['cost_code']; ?>
                </p>
            </div>
            <div class="or-card">
                <h4>PURPOSE / JUSTIFICATION</h4>
                <p>
                    <?php echo nl2br($request['justification']); ?>
                </p>
            </div>
            <div class="or-card">
                <h4>MANAGEMENT / CEO REMARKS</h4>

                <strong>
                    Status:
                    <?php echo $request['status']; ?>
                </strong>

                <p>
                    <?php
                    echo !empty($request['approval_remarks'])
                        ? nl2br($request['approval_remarks'])
                        : 'No management remarks yet.';
                    ?>
                </p>
            </div>
        </div>
        <!-- TABLE -->
        <div class="table-responsive">
            <table class="table or-table">
                <thead>
                    <tr>
                        <th>PRIORITY</th>
                        <th>REQUESTED ITEM</th>
                        <th>SPECIFICATIONS</th>
                        <th>QTY</th>
                        <th>UNIT PRICE</th>
                        <th>TOTAL</th>
                        <th>DATE NEEDED</th>
                        <th>REMARKS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $getItems = mysqli_query($conn, "
                    SELECT *
                    FROM purchase_request_items
                    WHERE request_id = '" . $request['id'] . "'
                ");
                    while ($item = mysqli_fetch_assoc($getItems)) {
                    ?>
                        <tr>
                            <td>
                                <?php echo $request['priority_level']; ?>
                            </td>
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
                                ₱<?php echo number_format(
                                        $item['unit_price'],
                                        2
                                    ); ?>
                            </td>
                            <td>
                                ₱<?php echo number_format(
                                        $item['item_total'],
                                        2
                                    ); ?>
                            </td>
                            <td>
                                <?php
                                echo date(
                                    'M d, Y',
                                    strtotime($request['date_needed'])
                                );
                                ?>
                            </td>
                            <td>
                                <?php echo $item['remarks']; ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php
                    $getGrandTotal = mysqli_query($conn, "
                         SELECT SUM(item_total) AS grand_total
                         FROM purchase_request_items
                         WHERE request_id = '" . $request['id'] . "'

");
                    $totalData = mysqli_fetch_assoc($getGrandTotal);
                    ?>
                    <tr>
                        <td colspan="6" style="text-align:right;font-weight:800;">
                            GRAND TOTAL
                        </td>
                        <td colspan="2" style="font-weight:800;color:#0d3b73;">
                            ₱<?php echo number_format(
                                    $totalData['grand_total'],
                                    2
                                ); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- FLOW -->
        <div class="or-flow">
            <div class="flow-card active">
                <strong>1. Requested</strong>
                <span>Purchase request submitted</span>
            </div>
            <div class="flow-card active">
                <strong>2. Budget Checked</strong>
                <span>Within approved cost code</span>
            </div>
            <div class="flow-card processing">
                <strong>3. Procurement</strong>
                <span>Awaiting procurement review</span>
            </div>
            <div class="flow-card">
                <strong>4. Approval</strong>
                <span>Pending management approval</span>
            </div>
            <div class="flow-card">
                <strong>5. Delivery</strong>
                <span>Awaiting supplier delivery</span>
            </div>
        </div>
        <!-- SIGNATURE -->
        <div class="or-signatures">
            <div class="or-sign-box">
                <div class="sig-line"></div>
                <strong>Requested by</strong>
                <p>
                    <?php echo $request['requested_by']; ?>
                </p>
            </div>
            <div class="or-sign-box">
                <div class="sig-line"></div>
                <strong>Budget verified by</strong>
                <p>Cost Control Department</p>
            </div>
            <div class="or-sign-box">
                <div class="sig-line"></div>
                <strong>Approved by</strong>
                <p>Project Manager</p>
            </div>
        </div>
    </div>
<?php } ?>
<script>
    function addItemRow() {
        let units = `
        <?php
        $getUnitsJs = mysqli_query($conn, "
            SELECT *
            FROM units
            ORDER BY unit_name ASC
        ");
        while ($unitJs = mysqli_fetch_assoc($getUnitsJs)) {
        ?>
            <option value="<?php echo $unitJs['unit_name']; ?>">
                <?php echo $unitJs['unit_name']; ?>
            </option>
        <?php } ?>
    `;
        let row = `
        <div class="item-row">
            <input
                type="text"
                name="item_name[]"
                placeholder="Item Name"
                required>
            <input
                type="text"
                name="specifications[]"
                placeholder="Specifications">
            <input
                type="number"
                name="quantity[]"
                placeholder="Quantity">
            <select name="unit[]" required>
                <option value="">
                    Unit
                </option>
                ${units}
            </select>
            <input
                type="number"
                step="0.01"
                name="unit_price[]"
                placeholder="Unit Price">
            <input
                type="text"
                name="remarks[]"
                placeholder="Remarks">
        </div>
    `;
        document
            .getElementById('items-container')
            .insertAdjacentHTML('beforeend', row);
    }
</script>