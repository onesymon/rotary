<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/rotary/includes/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /rotary/webpages/logout/login.php");
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die("Invalid transaction ID.");
}

$allowed_roles = ['1', '4', '5', '100'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: /rotary/dashboard.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT ct.*, 
           COALESCE(m.fullname, 'Unknown') AS member_name, 
           COALESCE(e.fullname, 'Unknown') AS encoded_by_name,
           COALESCE(pm.method_name, 'N/A') AS payment_method_name,
           s.currency
    FROM club_transactions ct
    LEFT JOIN members m ON ct.member_id = m.id
    LEFT JOIN members e ON ct.encoded_by = e.id
    LEFT JOIN payment_method pm ON ct.payment_method = pm.id
    JOIN settings s ON s.id = 1
    WHERE ct.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Transaction not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('../../../includes/header.php'); ?>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">

<?php include('../../../includes/nav.php'); ?>
<?php include('../../../includes/sidebar.php'); ?>

<div class="content-wrapper">
    <?php include('../../../includes/page_title.php'); ?>

    <section class="content">
        <div class="container-fluid">

            <div class="card shadow">
                <div class="card-header text-center bg-white">
                    <h3 class="mb-0 font-weight-bold">Rotary Club Transaction Receipt</h3>
                    <small class="text-muted">Official Proof of Club Transaction</small>
                </div>

                <div class="card-body" id="receipt-content">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Transaction ID:</strong> <?= htmlspecialchars($data['id']) ?><br>
                            <strong>Date:</strong> <?= date("F j, Y - g:i A", strtotime($data['transaction_date'])) ?>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <strong>Encoded By:</strong> <?= htmlspecialchars($data['encoded_by_name']) ?><br>
                            <strong>Member:</strong> <?= htmlspecialchars($data['member_name']) ?>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Purpose:</strong> <?= ucfirst(htmlspecialchars($data['entry_type'])) ?><br>
                            <strong>Payment Method:</strong> <?= htmlspecialchars($data['payment_method_name']) ?>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <strong>Amount Paid:</strong> <?= htmlspecialchars($data['currency']) . ' ' . number_format($data['amount'], 2) ?><br>
                            <strong>Reference #:</strong> <?= htmlspecialchars($data['reference_number'] ?? 'N/A') ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Remarks:</strong> <?= htmlspecialchars($data['remarks'] ?? 'None') ?>
                    </div>
                </div>

                <div class="card-footer text-center">
                    <button class="btn btn-outline-primary mr-2" onclick="downloadPDF()">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                    <button class="btn btn-outline-success mr-2" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <a href="manage_transactions.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Go Back
                    </a>
                </div>
            </div>

        </div>
    </section>
</div>

<?php include('../../../includes/footer.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    const element = document.getElementById('receipt-content');
    const opt = {
        margin: 0.5,
        filename: 'receipt_transaction_<?= $data['id'] ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>
</body>
</html>
