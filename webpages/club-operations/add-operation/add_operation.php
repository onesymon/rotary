<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/rotary/includes/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /rotary/webpages/logout/login.php");
    exit();
}

// Block members from accessing officer-only pages
if ($_SESSION['role'] !== '1' && $_SESSION['role'] !== '3' && $_SESSION['role'] !== '4' && $_SESSION['role'] !== '100') {
    header("Location: /rotary/dashboard.php");
    exit();
}

$response = array('success' => false, 'message' => '');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = trim($_POST['category']);
    $amount = floatval(str_replace(',', '', $_POST['amount']));
    $payment_date = $_POST['payment_date'];
    $paid_to = trim($_POST['paid_to']);
    $notes = trim($_POST['notes']);
    $status = $_POST['status'] ?? 'Unpaid';
    $encoded_by = $_SESSION['user_id'];

    if (empty($category) || empty($amount) || empty($payment_date)) {
        $response['message'] = 'Expense Type, Amount, and Payment Date are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO club_operations (category, amount, payment_date, paid_to, notes, status, encoded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdssssi", $category, $amount, $payment_date, $paid_to, $notes, $status, $encoded_by);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Expense added successfully!';
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
                <h3 class="card-title">Create New Club Operation</h3>
              </div>

          <form id="editProfileForm" method="post" action="" enctype="multipart/form-data">

                <div class="card-body">
                  <div class="form-group">
                    <label for="category">Expense Type</label>
                    <input type="text" class="form-control" name="category" required placeholder="e.g. Electricity Bill, Cleaning Service">
                  </div>

                  <div class="form-group">
                    <label for="amount">Total Amount (₱)</label>
                    <input type="text" class="form-control" name="amount" id="amount" required placeholder="Enter amount in pesos">
                  </div>

                  <div class="form-group">
                    <label for="payment_date">Payment Date</label>
                    <input type="date" class="form-control" name="payment_date" required>
                  </div>

                  <div class="form-group">
                    <label for="paid_to">Paid To (Optional)</label>
                    <input type="text" class="form-control" name="paid_to" placeholder="Name of person/company">
                  </div>

                  <div class="form-group">
                    <label for="notes">Additional Notes (Optional)</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Any extra information..."></textarea>
                  </div>

                  <div class="form-group">
                    <label for="status">Payment Status</label>
                    <select class="form-control" name="status">
                      <option value="Unpaid">Unpaid</option>
                      <option value="Paid">Paid</option>
                    </select>
                  </div>
                </div>

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Add Club Operation</button>
                  <a href="/rotary/webpages/club-operations/manage-operations/manage_operations.php" class="btn btn-success float-right">
                    <i class="fas fa-eye me-1"></i> View Club Operations
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
        <h5 class="modal-title" id="confirmModalLabel"><i class="fas fa-exclamation-circle"></i> Confirm Update</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        Are you sure you want to this type of expenses?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">No, Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmSubmit">Yes, Update</button>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript Dependencies -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script> <!-- Bootstrap -->

<!-- Custom Scripts -->
<script>
    function previewPhoto(event) {
        const reader = new FileReader();
        reader.onload = function(){
            const output = document.getElementById('photoPreview');
            output.src = reader.result;
            output.style.display = 'block';
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    $(document).ready(function () {
        let form = $('#editProfileForm');

        form.on('submit', function (e) {
            // If not confirmed yet, block and show modal
            if (!form.data('confirmed')) {
                e.preventDefault(); // stop submission
                $('#confirmModal').modal('show'); // show confirmation modal
            }
        });

        $('#confirmSubmit').on('click', function () {
            $('#confirmModal').modal('hide'); // hide modal
            $('#editProfileForm').data('confirmed', true).submit(); // set confirmed flag and resubmit
        });
    });
</script>
 

<script>
  // Formatting the amount input field to show commas
  document.addEventListener("DOMContentLoaded", function () {
    const amountInput = document.getElementById('amount');

    amountInput.addEventListener('input', function () {
      let value = this.value.replace(/[^0-9.]/g, ''); // Remove non-numeric characters except the dot
      this.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ','); // Add commas for thousands separator
    });
  });
</script>

</body>
</html>
