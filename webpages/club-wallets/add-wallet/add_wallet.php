<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/rotary/includes/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /rotary/webpages/logout/login.php");
    exit();
}

if (!in_array($_SESSION['role'], ['1', '3', '4', '100'])) {
    header("Location: /rotary/dashboard.php");
    exit();
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fund_name = trim($_POST['fund_name']);
    $description = trim($_POST['description']);
    $currency = trim($_POST['currency']);
    $status = trim($_POST['status']);
    $owner = trim($_POST['owner']);
    $current_balance = floatval($_POST['current_balance']); // Used instead of initial_amount
    $encoded_by = $_SESSION['user_id'];

    if (empty($fund_name) || $current_balance < 0) {
        $response['message'] = 'Fund name and starting balance are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO club_wallet_categories (fund_name, description, current_balance, currency, status, owner, encoded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsssi", $fund_name, $description, $current_balance, $currency, $status, $owner, $encoded_by);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Fund wallet added successfully!';
        } else {
            $response['message'] = 'Error: ' . $stmt->error;
        }

        $stmt->close();
    }
}
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
        <div class="row">
          <div class="col-md-12">

            <?php if ($response['success']): ?>
              <div class="alert alert-success"><?php echo $response['message']; ?></div>
            <?php elseif (!empty($response['message'])): ?>
              <div class="alert alert-danger"><?php echo $response['message']; ?></div>
            <?php endif; ?>

            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Create New Club Wallet</h3>
              </div>

              <form id="clubWalletForm" method="post" action="" enctype="multipart/form-data">

                <div class="card-body">

                  <!-- Fund Name Section -->
                  <div class="form-group">
                    <label for="fund_name">Fund Name</label>
                    <input type="text" class="form-control" name="fund_name" placeholder="e.g., Club Fund, Donations, Sponsorships" required>
                  </div>

                  <!-- Description Section -->
                  <div class="form-group">
                    <label for="description">Fund Description (Optional)</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Write a brief description about the fund..."></textarea>
                  </div>

                  <!-- Currency Section -->
                  <div class="form-group">
                    <label for="currency">Currency</label>
                    <input type="text" class="form-control" name="currency" value="PHP" placeholder="e.g., PHP, USD">
                  </div>

                  <!-- Status Section -->
                  <div class="form-group">
                    <label for="status">Is this Fund Active or Inactive?</label>
                    <select class="form-control" name="status">
                      <option value="Active">Active</option>
                      <option value="Inactive">Inactive</option>
                    </select>
                  </div>

                  <!-- Owner Section -->
                  <div class="form-group">
                    <label for="owner">Owner / Responsible Person (Optional)</label>
                    <input type="text" class="form-control" name="owner" placeholder="e.g., Treasurer, Member Name">
                  </div>

                  <!-- Starting Balance Section -->
                  <div class="form-group">
                    <label for="current_balance">Starting Balance (₱)</label>
                    <input type="number" step="0.01" class="form-control" name="current_balance" placeholder="Enter starting balance" required>
                  </div>

                </div>

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Add Club Wallet</button>
                  <a href="/rotary/webpages/club-wallets/manage-wallets/manage_wallets.php" class="btn btn-success float-right">
                    <i class="fas fa-eye"></i> View Club Wallets
                  </a>
                </div>
              </form>
            </div>

          </div>
        </div>
      </div>
    </section>
  </div>

  <?php include('../../../includes/footer.php'); ?>
</div>

 <!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-primary">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="confirmModalLabel"><i class="fas fa-exclamation-circle"></i> Confirm Wallet Creation</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        Are you sure you want to add this club wallet?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">No, Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmSubmit">Yes, Add</button>
      </div>
    </div>
  </div>
</div>


<!-- JavaScript Dependencies -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script> <!-- Bootstrap -->

<!-- Custom Scripts --><script>
$(document).ready(function () {
    const form = $('#clubWalletForm'); // your form ID

    form.on('submit', function (e) {
        if (!this.checkValidity()) {
            return; // Let browser handle invalid input
        }

        if (!form.data('confirmed')) {
            e.preventDefault(); // Stop submission
            $('#confirmModal').modal('show');
        }
    });

    $('#confirmSubmit').on('click', function () {
        $('#confirmModal').modal('hide');
        $('#clubWalletForm').data('confirmed', true).submit(); // Trigger submit after confirm
    });
});
</script>


 
</body>
</html>
