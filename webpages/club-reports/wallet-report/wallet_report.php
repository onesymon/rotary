<?php
session_start();
include_once($_SERVER['DOCUMENT_ROOT'] . '/rotary/includes/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /rotary/webpages/logout/login.php");
    exit();
}

if (!in_array($_SESSION['role'], ['1', '3', '4', '5', '6', '100'])) {
    header("Location: /rotary/dashboard.php");
    exit();
}

// Fetch all fund wallets
$funds = [];
$total_balance = 0;

$fundQuery = "
    SELECT cf.*, m.fullname AS encoded_by_name 
    FROM club_wallet_categories cf 
    LEFT JOIN members m ON cf.encoded_by = m.id 
    ORDER BY cf.status DESC, cf.fund_name ASC
";
$fundResult = $conn->query($fundQuery);

while ($row = $fundResult->fetch_assoc()) {
    $funds[] = $row;
    $total_balance += floatval($row['current_balance']);
}

// Summary calculations
$summary = [
    'total_funds' => count($funds),
    'active' => count(array_filter($funds, fn($f) => $f['status'] === 'Active')),
    'inactive' => count(array_filter($funds, fn($f) => $f['status'] === 'Inactive')),
    'total_balance' => $total_balance,
];

// Fetch fund transactions (not used anymore after removal)
$transactionQuery = "
    SELECT t.*, f.fund_name, 
           m.fullname AS encoded_by_name,
           c.fullname AS contributor_name
    FROM club_wallet_transactions t
    LEFT JOIN club_wallet_categories f ON t.fund_id = f.id
    LEFT JOIN members m ON t.encoded_by = m.id
    LEFT JOIN members c ON t.member_id = c.id
    ORDER BY t.transaction_date DESC
";
$transactions = $conn->query($transactionQuery);
?>

<?php include('../../../includes/header.php'); ?>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
    <?php include('../../../includes/nav.php'); ?>
    <?php include('../../../includes/sidebar.php'); ?>

    <div class="content-wrapper">
        <?php include('../../../includes/page_title.php'); ?>

        <section class="content">
            <div class="container-fluid">

                <!-- Summary Boxes -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="info-box bg-primary">
                            <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Wallets</span>
                                <span class="info-box-number"><?= $summary['total_funds'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Active</span>
                                <span class="info-box-number"><?= $summary['active'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-danger">
                            <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Inactive</span>
                                <span class="info-box-number"><?= $summary['inactive'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-warning">
                            <span class="info-box-icon"><i class="fas fa-coins"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Balance</span>
                                <span class="info-box-number">₱<?= number_format($summary['total_balance'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wallet Cards -->
                <div class="row">
                    <?php foreach ($funds as $fund): ?>
                        <div class="col-md-4">
                            <div class="card card-<?= $fund['status'] === 'Active' ? 'success' : 'secondary' ?>">
                                <div class="card-header">
                                    <h3 class="card-title"><?= htmlspecialchars($fund['fund_name']) ?></h3>
                                </div>
                                <div class="card-body">
                                    <p><strong>Balance:</strong> <?= '₱' . number_format($fund['current_balance'], 2) ?></p>
                                    <p><strong>Status:</strong> <?= htmlspecialchars($fund['status']) ?></p>
                                    <p><strong>Owner:</strong> <?= htmlspecialchars($fund['owner'] ?: 'N/A') ?></p>
                                    <p><strong>Encoded By:</strong> <?= htmlspecialchars($fund['encoded_by_name'] ?? 'Unknown') ?></p>
                                    <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($fund['description'])) ?></p>
                                </div>
                                <div class="card-footer">
                                    <a href="wallet_details.php?id=<?= $fund['id'] ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </section>
    </div>

    <?php include('../../../includes/footer.php'); ?>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
