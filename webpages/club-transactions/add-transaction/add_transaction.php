<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/rotary/includes/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /rotary/webpages/logout/login.php");
    exit();
}

if ($_SESSION['role'] !== '1' && $_SESSION['role'] !== '4' && $_SESSION['role'] !== '100') {
    header("Location: /rotary/dashboard.php");
    exit();
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sourceType = $_POST['source_type'];
    $memberId = ($sourceType === 'Member') ? ($_POST['member_id'] ?? null) : null;
    $externalSource = ($sourceType === 'External') ? trim($_POST['external_source'] ?? '') : null;
    $fundWalletId = ($sourceType === 'Fund Wallet') ? $_POST['fund_wallet_id'] ?? null : null;

    $amount = (float) str_replace(',', '', $_POST['amount']);
    $paymentMethod = $_POST['payment_method'];
    $category = $_POST['category'];
    $activityId = $_POST['activity_id'] ?? null;
    $customActivity = $_POST['custom_activity'] ?? null;
    $entryType = $_POST['entry_type'] ?? 'Income';
    $remarks = $_POST['remarks'] ?? '';
    $referenceNumber = trim($_POST['reference_number'] ?? '');
    $encodedBy = $_SESSION['user_id'];

    if (!$amount || !$paymentMethod || !$entryType || !$sourceType || !$category) {
        $response['message'] = 'Please fill all required fields.';
    } elseif ($sourceType === 'External' && empty($externalSource)) {
        $response['message'] = 'Please enter a source name for external transactions.';
    } elseif ($sourceType === 'Fund Wallet' && empty($fundWalletId)) {
        $response['message'] = 'Please select a fund wallet.';
    } elseif ($category === 'Other Purpose' && empty($customActivity)) {
        $response['message'] = 'Please specify the other purpose.';
    } else {
        if (empty($referenceNumber)) {
            $prefix = 'REF';
            $methodQuery = $conn->prepare("SELECT method_name FROM payment_method WHERE id = ?");
            $methodQuery->bind_param("i", $paymentMethod);
            $methodQuery->execute();
            $result = $methodQuery->get_result();
            if ($row = $result->fetch_assoc()) {
                switch (strtolower($row['method_name'])) {
                    case 'cash': $prefix = 'CSH'; break;
                    case 'gcash': $prefix = 'GC'; break;
                    case 'maya': $prefix = 'MY'; break;
                    case 'bank transfer': $prefix = 'BT'; break;
                }
            }
            $randomDigits = mt_rand(10000000, 99999999);
            $referenceNumber = "$prefix-$randomDigits";
        }

        $stmt = $conn->prepare("INSERT INTO club_transactions 
            (member_id, external_source, amount, payment_method, category, activity_id, remarks, reference_number, encoded_by, entry_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $finalActivityId = ($category === 'Other Purpose') ? null : $activityId;
        $finalRemarks = $remarks;
        if ($category === 'Other Purpose') {
            $finalRemarks = "[Other Purpose: $customActivity]" . ($remarks ? " - $remarks" : '');
        }

        $stmt->bind_param("isdssissss", $memberId, $externalSource, $amount, $paymentMethod, $category, $finalActivityId, $finalRemarks, $referenceNumber, $encodedBy, $entryType);

        if ($stmt->execute()) {
            $transactionId = $stmt->insert_id;

            if ($category === 'Club Project' && $sourceType === 'Fund Wallet') {
                $stmt = $conn->prepare("UPDATE club_wallet_categories SET current_balance = current_balance - ? WHERE id = ?");
                $stmt->bind_param("di", $amount, $fundWalletId);
                $stmt->execute();

               

                $stmt2 = $conn->prepare("SELECT current_funding, target_funding FROM club_projects WHERE id = ?");
                $stmt2->bind_param("i", $activityId);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                if ($row = $result2->fetch_assoc()) {
                    $newFunding = $row['current_funding'] + $amount;
                    $remaining = max(0, $row['target_funding'] - $newFunding);
                    $stmt3 = $conn->prepare("UPDATE club_projects SET current_funding = ?, remaining_funding = ? WHERE id = ?");
                    $stmt3->bind_param("ddi", $newFunding, $remaining, $activityId);
                    $stmt3->execute();
                }
            }

            if ($category === 'Club Fund') {
                $transactionType = ($entryType === 'Expense') ? 'withdrawal' : 'deposit';
                $balanceSQL = ($transactionType === 'withdrawal') 
                    ? "UPDATE club_wallet_categories SET current_balance = current_balance - ? WHERE id = ?"
                    : "UPDATE club_wallet_categories SET current_balance = current_balance + ? WHERE id = ?";
                $stmt4 = $conn->prepare($balanceSQL);
                $stmt4->bind_param("di", $amount, $activityId);
                $stmt4->execute();

                $stmt5 = $conn->prepare("INSERT INTO club_wallet_transactions 
                    (fund_id, transaction_type, amount, remarks, member_id, reference_id, encoded_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt5->bind_param("ssdssii", $activityId, $transactionType, $amount, $remarks, $memberId, $transactionId, $encodedBy);
                $stmt5->execute();
            }

            $response['success'] = true;
            $response['message'] = 'Transaction added successfully!';
        } else {
            $response['message'] = 'Error: ' . $stmt->error;
        }
    }
}
?>

<?php include('../../../includes/header.php'); ?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />

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
                <h3 class="card-title">Create New Club Transaction</h3>
              </div>

           <form id="editProfileForm" method="post" action="" enctype="multipart/form-data">

                <div class="card-body">

                  <!-- Source Type Section -->
                  <div class="form-group">
                    <label for="source_type">Who is this transaction from?</label>
                    <select class="form-control" name="source_type" id="source_type" required>
                      <option value="Member">Member</option>
                      <option value="External">External</option>
                      <option value="Fund Wallet">Club Wallet</option>
                    </select>
                  </div>

                  <!-- Member Search Section -->
                  <div class="form-group" id="member_section">
                    <label for="search_value">Search for Member</label>
                    <input type="text" id="search_value" name="search_value" class="form-control" autocomplete="off">
                    <input type="hidden" name="member_id" id="member_id" />
                    <div id="suggestions" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                  </div>

                  <!-- External Source Section -->
                  <div class="form-group d-none" id="external_section">
                    <label for="external_source">Enter Name of External Source</label>
                    <input type="text" name="external_source" id="external_source" class="form-control">
                  </div>

                  <!-- Fund Wallet Section -->
                  <div class="form-group d-none" id="fund_wallet_section">
                    <label for="fund_wallet_id">Select a Club Wallet</label>
                    <select class="form-control" name="fund_wallet_id" id="fund_wallet_id">
                      <option value="">Select Wallet</option>
                      <?php
                      $funds = $conn->query("SELECT id, fund_name FROM club_wallet_categories ORDER BY fund_name ASC");
                      while ($f = $funds->fetch_assoc()) {
                          echo "<option value='{$f['id']}'>{$f['fund_name']}</option>";
                      }
                      ?>
                    </select>
                  </div>

                  <!-- Payment Method Section -->
                  <div class="form-group">
                    <label for="payment_method">How was the payment made?</label>
                    <select class="form-control" name="payment_method" id="payment_method" required>
                      <option value="">Choose Payment Method</option>
                      <?php
                      $paymentMethodQuery = "SELECT id, method_name FROM payment_method";
                      $paymentMethodResults = $conn->query($paymentMethodQuery);
                      while ($type = $paymentMethodResults->fetch_assoc()) {
                          // Assign an ID to the "System" option so we can hide it dynamically
                          $extraAttr = strtolower($type['method_name']) === 'system' ? 'data-system-option="true"' : '';
                          echo "<option value='{$type['id']}' $extraAttr>{$type['method_name']}</option>";
                      }
                      ?>
                    </select>
                  </div>

                  <!-- Entry Type Section -->
                  <div class="form-group">
                    <label for="entry_type">Is this money coming in or going out?</label>
                    <select class="form-control" name="entry_type" id="entry_type" required>
                      <option value="Income">Money In</option>
                      <option value="Expense">Money Out</option>
                      <option value="Contribution">Contribution</option>
                    </select>
                  </div>

                  <!-- Category Section -->
                  <div class="form-group">
                    <label for="category">What is this transaction for?</label>
                    <select class="form-control" name="category" id="category" required>
                      <option value="">Choose Category</option>
                      <option value="Club Project">Club Project</option>
                      <option value="Club Event">Club Event</option>
                      <option value="Club Operation">Club Operation</option>
                      <option value="Club Fund">Club Wallet</option>
                    </select>
                  </div>

                  <!-- Activity Section -->
                  <div class="form-group" id="activity_container">
                    <label id="activity_label" for="activity_id">Related Project/Event/Operation/Wallet</label>
                    <select class="form-control" name="activity_id" id="activity_id">
                      <option value="">Select Option</option>
                    </select>
                  </div>

                  <!-- Custom Activity Section -->
                  <div class="form-group d-none" id="custom_activity_container">
                    <label for="custom_activity">Please specify the purpose</label>
                    <input type="text" class="form-control" name="custom_activity" id="custom_activity" placeholder="e.g. Special Donation, Misc Income">
                  </div>

                  <!-- Amount Section -->
                  <div class="form-group">
                    <label for="amount">Amount (₱)</label>
                    <input type="text" class="form-control" name="amount" id="amount" required placeholder="Enter amount in PHP">
                  </div>

                  <!-- Reference Number Section -->
                  <div class="form-group">
                    <label for="reference_number">Reference Number (Optional)</label>
                    <input type="text" class="form-control" name="reference_number" placeholder="Leave blank to auto-generate">
                  </div>

                  <!-- Remarks Section -->
                  <div class="form-group">
                    <label for="remarks">Additional Notes (Optional)</label>
                    <input type="text" class="form-control" name="remarks" placeholder="e.g. Remarks or extra details">
                  </div>

                </div>

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Add Transaction</button>
                  <a href="/rotary/webpages/club-transactions/manage-transactions/manage_transactions.php" class="btn btn-success float-right">
                    <i class="fas fa-eye me-1"></i> View Club Transactions
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
        Are you sure you want to update your profile information?
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

document.addEventListener("DOMContentLoaded", function () {
    const amountInput = document.getElementById('amount');
    
    // Format the amount as the user types
    amountInput.addEventListener('input', function () {
        let value = this.value.replace(/[^0-9.]/g, ''); // Remove non-numeric characters except the dot
        this.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ','); // Add commas for thousands separator
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const sourceType = document.getElementById("source_type");
    const paymentMethod = document.getElementById("payment_method");
    const systemOption = paymentMethod.querySelector('option[data-system-option="true"]');

    if (sourceType.value === "Member" && systemOption) {
        systemOption.classList.add("d-none");
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById("search_value");
    const suggestionsBox = document.getElementById("suggestions");
    const hiddenInput = document.getElementById("member_id");
    const categorySelect = document.getElementById("category");
    const activitySelect = document.getElementById("activity_id");
    const activityLabel = document.getElementById("activity_label");
    const sourceType = document.getElementById("source_type");
    const entryType = document.getElementById("entry_type");
    const memberSection = document.getElementById("member_section");
    const externalSection = document.getElementById("external_section");
    const fundWalletSection = document.getElementById("fund_wallet_section");
    const activityContainer = document.getElementById("activity_container");
    const customActivityContainer = document.getElementById("custom_activity_container");

    input.addEventListener("input", function () {
        const query = this.value;
        if (query.length < 1) {
            suggestionsBox.innerHTML = '';
            return;
        }

        fetch("/rotary/includes/member_suggestions.php?query=" + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                suggestionsBox.innerHTML = '';
                data.forEach(item => {
                    const div = document.createElement("div");
                    div.classList.add("list-group-item", "list-group-item-action");
                    div.textContent = item.fullname + " (" + item.membership_number + ")";
                    div.onclick = () => {
                        input.value = item.fullname + " (" + item.membership_number + ")";
                        hiddenInput.value = item.id;
                        suggestionsBox.innerHTML = '';
                    };
                    suggestionsBox.appendChild(div);
                });
            });
    });

    sourceType.addEventListener("change", () => {
        const type = sourceType.value;

        memberSection.classList.toggle("d-none", type !== "Member");
        externalSection.classList.toggle("d-none", type !== "External");
        fundWalletSection.classList.toggle("d-none", type !== "Fund Wallet");

        const paymentMethod = document.getElementById("payment_method");
        const systemOption = paymentMethod.querySelector('option[data-system-option="true"]');

        if (type === "Fund Wallet") {
            entryType.value = "Contribution";
            entryType.setAttribute("disabled", "disabled");
        } else {
            entryType.removeAttribute("disabled");
        }

        if (type === "External" || type === "Fund Wallet") {
            // Show System option
            if (systemOption) systemOption.classList.remove("d-none");

            // Auto-select and disable dropdown
            fetch("/rotary/includes/payment_method.php?name=System")
                .then(res => res.json())
                .then(data => {
                    if (data && data.id) {
                        paymentMethod.value = data.id;
                        paymentMethod.setAttribute("disabled", "disabled");
                    }
                });
        } else {
            // Enable and show full options, hide System if exists
            paymentMethod.removeAttribute("disabled");
            paymentMethod.value = "";
            if (systemOption) systemOption.classList.add("d-none");
        }
    });

    categorySelect.addEventListener("change", function () {
        const category = this.value;
        if (category === "Other Purpose") {
            activityContainer.classList.add("d-none");
            customActivityContainer.classList.remove("d-none");
        } else {
            activityContainer.classList.remove("d-none");
            customActivityContainer.classList.add("d-none");
        }

        let label = "Related Project/Event/Operation/Wallet";
        if (category === "Club Project") label = "Choose Club Project";
        else if (category === "Club Event") label = "Choose Club Event";
        else if (category === "Club Operation") label = "Choose Club Operation";
        else if (category === "Club Fund") label = "Choose Club Wallet";
        activityLabel.textContent = label;

        if (category !== "Other Purpose") {
            fetch("/rotary/includes/get_activities.php?category=" + encodeURIComponent(category))
                .then(res => res.json())
                .then(data => {
                    activitySelect.innerHTML = '<option value="">Select Option</option>';
                    data.forEach(item => {
                        const opt = document.createElement("option");
                        opt.value = item.id;
                        opt.textContent = item.title;
                        activitySelect.appendChild(opt);
                    });
                });
        }
    });
});
</script>
</body>
</html>