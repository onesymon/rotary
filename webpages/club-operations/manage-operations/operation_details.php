<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/rotary/includes/config.php');

if ($_SESSION['role'] !== '1' && $_SESSION['role']!=='5'&& $_SESSION['role'] !== '100' ) {
    header("Location: /rotary/dashboard.php");
    exit();
}

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "Operation not found.";
    exit();
}

$stmt = $conn->prepare("
    SELECT co.*, m.fullname AS encoded_by_name, s.currency
    FROM club_operations co
    LEFT JOIN members m ON co.encoded_by = m.id
    JOIN settings s ON s.id = 1
    WHERE co.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    echo "Operation not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('../../../includes/header.php'); ?>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

<?php include('../../../includes/nav.php'); ?>
<?php include('../../../includes/sidebar.php'); ?>

<div class="content-wrapper">
    <?php 
    // You may reuse page_title.php or directly insert title here
    ?>
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Operation Details</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/rotary/dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Operation Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Operation Information</h3>
                </div>

                <div class="card-body" id="receipt-content">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Operation ID:</strong> <?= htmlspecialchars($data['id']) ?><br>
                            <strong>Payment Date:</strong> <?= date("F j, Y", strtotime($data['payment_date'])) ?>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <strong>Encoded By:</strong> <?= htmlspecialchars($data['encoded_by_name']) ?><br>
                            <strong>Category:</strong> <?= htmlspecialchars($data['category']) ?>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Amount:</strong> <?= htmlspecialchars($data['currency']) . ' ' . number_format($data['amount'], 2) ?><br>
                            <strong>Paid To:</strong> <?= htmlspecialchars($data['paid_to']) ?>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <strong>Status:</strong> <?= htmlspecialchars($data['status']) ?><br>
                            <strong>Notes:</strong> <?= htmlspecialchars($data['notes']) ?: 'N/A' ?>
                        </div>
                    </div>
                </div>

                <div class="card-footer text-center">
                    <button class="btn btn-outline-primary mr-2" onclick="downloadPDF()">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>

                    <button class="btn btn-outline-success mr-2" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>

                    <a href="/rotary/webpages/club-operations/manage-operations/manage_operations.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Go Back
                    </a>
                </div>

            </div>

        </div>
    </section>
</div>

<?php include('../../../includes/footer.php'); ?>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    const element = document.getElementById('receipt-content');
    const opt = {
        margin: 0.5,
        filename: 'operation_details_<?= htmlspecialchars($data['id']) ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>
</body>
</html>
