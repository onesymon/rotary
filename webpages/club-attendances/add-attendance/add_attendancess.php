<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/rotary/includes/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /rotary/webpages/logout/login.php");
    exit();
}

if ($_SESSION['role'] !== '1' && $_SESSION['role'] !== '3' && $_SESSION['role'] !== '100') {
    header("Location: /rotary/dashboard.php");
    exit();
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $memberId = $_POST['member_id'] ?? null;
    $category = $_POST['category'] ?? null;
    $activity_id = $_POST['activity_id'] ?? null;
    $remarks = trim($_POST['remarks'] ?? '');
    $status = $_POST['status'] ?? 'Present';
    $createdBy = $_SESSION['user_id'];

    if (!$memberId || !$category || !$activity_id) {
        $response['message'] = 'Please complete all required fields.';
    } else {
        $sql = "INSERT INTO club_attendances 
                (member_id, category, activity_id, attendance_date, remarks, status, encoded_by)
                VALUES (?, ?, ?, CURRENT_TIMESTAMP(), ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isisss", $memberId, $category, $activity_id, $remarks, $status, $createdBy);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Attendance successfully recorded!';
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
                <h3 class="card-title">Create Club Member Attendance</h3>
              </div>

              <form id="editProfileForm" method="post" action="" enctype="multipart/form-data">

                <div class="card-body">

                  <!-- Member Search Section -->
                  <div class="form-group">
                    <label for="search_value">Search for a Member</label>
                    <input type="text" id="search_value" name="search_value" class="form-control" placeholder="e.g., Juan Dela Cruz or 12345" autocomplete="off" required>
                    <input type="hidden" name="member_id" id="member_id" />
                    <div id="suggestions" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                  </div>

                  <!-- Category Section -->
                  <div class="form-group">
                    <label for="category">Select Attendance Category</label>
                    <select name="category" id="category" class="form-control" required>
                      <option value="">Select Category</option>
                      <option value="Club Project">Club Project</option>
                      <option value="Club Event">Club Event</option>
                    </select>
                  </div>

                  <!-- Activity Section -->
                  <div class="form-group">
                    <label for="activity_id" id="activity_label">Select Related Activity</label>
                    <select name="activity_id" id="activity_id" class="form-control" required>
                      <option value="">Select Activity</option>
                    </select>
                  </div>

                  <!-- Remarks Section -->
                  <div class="form-group">
                    <label for="remarks">Add Any Remarks (Optional)</label>
                    <input type="text" class="form-control" name="remarks" placeholder="e.g., Arrived late, Special mention">
                  </div>

                  <!-- Status Section -->
                  <div class="form-group">
                    <label for="status">Member Status</label>
                    <select name="status" id="status" class="form-control">
                      <option value="Present">Present</option>
                      <option value="Late">Late</option>
                    </select>
                  </div>

                </div>

                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Add Attendance</button>
                  <a href="/rotary/webpages/club-attendances/manage-attendances/manage_attendances.php" class="btn btn-success float-right">
                    <i class="fas fa-eye"></i> View Club Attendances
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>


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
        Are you sure you want to submit?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">No, Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmSubmit">Yes, Update</button>
      </div>
    </div>
  </div>
</div>

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
    const input = document.getElementById("search_value");
    const suggestionsBox = document.getElementById("suggestions");
    const hiddenInput = document.getElementById("member_id");
    const categorySelect = document.getElementById("category");
    const activityLabel = document.getElementById("activity_label");
    const activitySelect = document.getElementById("activity_id");

    // Member Search Functionality
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

    // Clear suggestions box if user clicks outside
    document.addEventListener("click", function (e) {
        if (!suggestionsBox.contains(e.target) && e.target !== input) {
            suggestionsBox.innerHTML = '';
        }
    });

    // Activity Selector based on Category
    categorySelect.addEventListener("change", function () {
        const category = this.value;
        activityLabel.textContent = category === 'Club Project' ? 'Select Club Project' :
                                    category === 'Club Event' ? 'Select Club Event' :
                                    'Select Activity';

        activitySelect.innerHTML = '<option value="">Loading...</option>';
        fetch(`/rotary/includes/get_activities.php?category=${encodeURIComponent(category)}`)
            .then(response => response.json())
            .then(data => {
                activitySelect.innerHTML = '<option value="">Select Activity</option>';
                data.forEach(item => {
                    const option = document.createElement("option");
                    option.value = item.id;
                    option.textContent = item.title;
                    activitySelect.appendChild(option);
                });
            });
    });
});
</script>

</body>
</html>
