<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/rotary/includes/config.php');

if ($_SESSION['role'] !== '3' && $_SESSION['role'] !== '100') {
    header("Location: /rotary/dashboard.php");
    exit();
}

$response = array('success' => false, 'message' => '');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $location = trim($_POST['location']);
    $status = $_POST['status'] ?? 'Upcoming';
    $encoded_by = $_SESSION['user_id'];
    $needs_funding = $_POST['needs_funding'] ?? 'no';

    if ($needs_funding === 'yes') {
        $target_funding = floatval(str_replace(',', '', $_POST['target_funding']));
        $current_funding = 0.00;
        $remaining_funding = $target_funding;
    } else {
        $target_funding = 0.00;
        $current_funding = 0.00;
        $remaining_funding = "Doesn't need funds";
    }

    if (empty($title) || empty($event_date)) {
        $response['message'] = 'Event Name and Date are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO club_events (title, description, target_funding, current_funding, remaining_funding, event_date, event_time, location, status, encoded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddsssssi", $title, $description, $target_funding, $current_funding, $remaining_funding, $event_date, $event_time, $location, $status, $encoded_by);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'The event has been added successfully!';
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
                <h3 class="card-title">Create New Club Event</h3>
              </div>

       <form id="editProfileForm" method="post" action="" enctype="multipart/form-data">

                <div class="card-body">

                  <div class="form-group">
                    <label for="title">Event Name</label>
                    <input type="text" class="form-control" name="title" placeholder="What is the name of the event?" required>
                  </div>

                  <div class="form-group">
                    <label for="description">Event Description (Optional)</label>
                    <textarea class="form-control" name="description" rows="4" placeholder="What is this event about?"></textarea>
                  </div>

                  <div class="form-group">
                    <label for="event_date">When will it happen?</label>
                    <input type="date" class="form-control" name="event_date" required>
                  </div>

                  <div class="form-group">
                    <label for="event_time">What time does it start? (Optional)</label>
                    <input type="time" class="form-control" name="event_time">
                  </div>

                  <div class="form-group">
                    <label for="location">Where is the event? (Optional)</label>
                    <input type="text" class="form-control" name="location" placeholder="Enter the venue or place">
                  </div>

                  <div class="form-group">
                    <label>Do you need funds for this event?</label>
                    <select class="form-control" id="needs_funding" name="needs_funding" required onchange="toggleFundingField()">
                      <option value="no">No</option>
                      <option value="yes">Yes</option>
                    </select>
                  </div>

                  <div class="form-group" id="fundingField" style="display: none;">
                    <label for="target_funding">How much funding do you need?</label>
                    <input type="text" class="form-control" name="target_funding" id="target_funding" placeholder="Enter target amount in pesos">
                  </div>

                  <div class="form-group">
                    <label for="status">What is the event status?</label>
                    <select class="form-control" name="status">
                      <option value="Upcoming">Upcoming</option>
                      <option value="Ongoing">Ongoing</option>
                      <option value="Completed">Completed</option>
                    </select>
                  </div>

                </div>

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Add Club Event</button>
                  <a href="/rotary/webpages/club-events/manage-events/manage_events.php" class="btn btn-success float-right">
                    <i class="fas fa-eye me-1"></i> View Club Events
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
        Are you sure you want to add this event?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">No, Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmSubmit">Yes, Update</button>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript Dependencies -->
 
<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    function toggleFundingField() {
        var needsFunding = document.getElementById('needs_funding').value;
        var fundingField = document.getElementById('fundingField');

        if (needsFunding === 'yes') {
            fundingField.style.display = 'block';
            document.getElementById('target_funding').required = true;
        } else {
            fundingField.style.display = 'none';
            document.getElementById('target_funding').required = false;
            document.getElementById('target_funding').value = '';
        }
    }

    // Add this script to automatically format the amount input field with commas for user-friendly display
    document.addEventListener("DOMContentLoaded", function () {
        const amountInput = document.getElementById('target_funding');

        amountInput.addEventListener('input', function () {
            let value = this.value.replace(/[^0-9.]/g, ''); // Remove non-numeric characters except the dot
            this.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ','); // Add commas for thousands separator
        });
    });
</script>

</body>
</html>
