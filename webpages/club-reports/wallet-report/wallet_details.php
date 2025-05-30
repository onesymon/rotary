<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/rotary/includes/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /rotary/webpages/logout/login.php");
    exit();
}

if (!in_array($_SESSION['role'], ['1', '3', '4', '5', '6', '100'])) {
    header("Location: /rotary/dashboard.php");
    exit();
}

// Validate and fetch wallet ID
$wallet_id = $_GET['id'] ?? null;
if (!$wallet_id) {
    echo "Invalid wallet ID.";
    exit();
}

// Fetch wallet details
$stmt = $conn->prepare("
    SELECT w.*, m.fullname AS encoded_by_name 
    FROM club_wallet_categories w 
    LEFT JOIN members m ON w.encoded_by = m.id 
    WHERE w.id = ?
");
$stmt->bind_param("i", $wallet_id);
$stmt->execute();
$wallet = $stmt->get_result()->fetch_assoc();

if (!$wallet) {
    echo "Wallet not found.";
    exit();
}

// Fetch wallet transactions
$tx_stmt = $conn->prepare("
    SELECT t.*, 
           m.fullname AS encoded_by_name, 
           c.fullname AS contributor_name
    FROM club_wallet_transactions t
    LEFT JOIN members m ON t.encoded_by = m.id
    LEFT JOIN members c ON t.member_id = c.id
    WHERE t.fund_id = ?
    ORDER BY t.transaction_date DESC
");
$tx_stmt->bind_param("i", $wallet_id);
$tx_stmt->execute();
$transactions = $tx_stmt->get_result();
?>

<?php include('../../../includes/header.php'); ?>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php include('../../../includes/nav.php'); ?>
    <?php include('../../../includes/sidebar.php'); ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1>Wallet Details: <?= htmlspecialchars($wallet['fund_name']) ?></h1>
                <a href="wallet_report.php" class="btn btn-sm btn-secondary">Back to Wallets</a>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">

                <!-- Wallet Info -->
                <div class="card">
                    <div class="card-header bg-info">
                        <h3 class="card-title">Wallet Information</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?= htmlspecialchars($wallet['fund_name']) ?></p>
                        <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($wallet['description'])) ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($wallet['status']) ?></p>
                        <p><strong>Currency:</strong> <?= htmlspecialchars($wallet['currency']) ?></p>
                        <p><strong>Current Balance:</strong> <?= number_format($wallet['current_balance'], 2) ?></p>
                        <p><strong>Owner:</strong> <?= htmlspecialchars($wallet['owner'] ?: 'N/A') ?></p>
                        <p><strong>Encoded By:</strong> <?= htmlspecialchars($wallet['encoded_by_name']) ?></p>
                        <p><strong>Encoded At:</strong> <?= date('M d, Y | h:ia', strtotime($wallet['encoded_at'])) ?></p>
                    </div>
                </div>

                <!-- Wallet Transactions -->
                <div class="card">
                    <div class="card-header bg-warning">
                        <h3 class="card-title">Wallet Transactions</h3>
                    </div>
                    <div class="card-body">
                        <table id="walletTransactions" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Contributor</th>
                                    <th>Remarks</th>
                                    <th>Encoded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $counter = 1;
                                while ($tx = $transactions->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td>
                                            <span class="badge badge-<?= $tx['transaction_type'] === 'deposit' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($tx['transaction_type']) ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($tx['amount'], 2) ?></td>
                                        <td><?= date('M d, Y | h:ia', strtotime($tx['transaction_date'])) ?></td>
                                        <td><?= htmlspecialchars($tx['contributor_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($tx['remarks']) ?></td>
                                        <td><?= htmlspecialchars($tx['encoded_by_name'] ?? 'Unknown') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </section>
    </div>

    <?php include('../../../includes/footer.php'); ?>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(function () {
        $('#walletTransactions').DataTable({
            responsive: true,
            autoWidth: false,
            order: [[3, 'desc']]
        });
    });
</script>
</body>
</html>
