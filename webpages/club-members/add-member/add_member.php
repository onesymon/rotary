<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/rotary/includes/config.php');

if ($_SESSION['role'] !== '1' && $_SESSION['role'] !== '3' && $_SESSION['role'] !== '100') {
    header("Location: /rotary/dashboard.php");
    exit();
}

$response = ['success' => false, 'message' => ''];

function generateUniqueFileName($originalName) {
    $timestamp = time();
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return $timestamp . '_' . uniqid() . '.' . $extension;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contactNumber = $_POST['contactNumber'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $occupation = $_POST['occupation'];
    $password = $_POST['password'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $membershipNumber = 'CA-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

    $dobDate = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($dobDate)->y;

    if ($age < 18) {
        $response['message'] = 'You must be at least 18 years old.';
    } else {
        $uniquePhotoName = 'default.jpg';
        if (!empty($_FILES['photo']['name'])) {
            $uploadedPhoto = $_FILES['photo'];
            $uniquePhotoName = generateUniqueFileName($uploadedPhoto['name']);
            move_uploaded_file($uploadedPhoto['tmp_name'], 'uploads/member_photos/' . $uniquePhotoName);
        }

        $insertQuery = "INSERT INTO members (fullname, dob, gender, contact_number, email, address, occupation, membership_number, photo, password, created_at) 
                        VALUES ('$fullname', '$dob', '$gender', '$contactNumber', '$email', '$address', '$occupation',
                                '$membershipNumber', '$uniquePhotoName','$hashedPassword', NOW())";

        if ($conn->query($insertQuery) === TRUE) {
            $response['success'] = true;
            $response['message'] = 'Member added successfully! Membership Number: ' . $membershipNumber;
        } else {
            $response['message'] = 'Error: ' . $conn->error;
        }
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
                            <div class="alert alert-success alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                <h5><i class="icon fas fa-check"></i> Success</h5>
                                <?php echo $response['message']; ?>
                            </div>
                        <?php elseif (!empty($response['message'])): ?>
                            <div class="alert alert-danger alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                <h5><i class="icon fas fa-ban"></i> Error</h5>
                                <?php echo $response['message']; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Client-side validation alert -->
                        <div id="clientValidationAlert" class="alert alert-danger alert-dismissible" style="display: none;">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-ban"></i> Error</h5>
                            <span id="clientValidationMessage"></span>
                        </div>

                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Register New Club Member</h3>
                            </div>

                            <form id="editProfileForm" method="post" action="" enctype="multipart/form-data">
                                <div class="card-body">

                                <h5 class="text-primary"><i class="fas fa-id-card"></i> Personal Information</h5>
                                    <div class="form-group">
                                        <label for="fullname">Full Name</label>
                                        <input type="text" class="form-control" id="fullname" name="fullname" placeholder="e.g., Juan Dela Cruz" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="dob">WDate of Birth</label>
                                        <input type="date" class="form-control" id="dob" name="dob" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="gender">Select the member's gender</label>
                                        <select class="form-control" id="gender" name="gender" required>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                     <hr>

                                      <!-- CONTACT INFO -->
                                    <h5 class="text-primary"><i class="fas fa-phone-alt"></i> Contact Information</h5>
                                 
                                    <div class="form-group">
                                        <label for="contactNumber">Member’s Contact Number</label>
                                        <input type="tel" 
                                            class="form-control" 
                                            id="contactNumber" 
                                            name="contactNumber" 
                                            placeholder="e.g. 09123456789" 
                                            pattern="^09\d{9}$" 
                                            maxlength="11" 
                                            inputmode="numeric"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                          
                                            required>
                                        <small class="form-text text-muted">Must start with 09 and be exactly 11 digits. Only numbers are allowed.</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" placeholder="e.g., juan@example.com" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="address">Where does the member live?</label>
                                        <input type="text" class="form-control" id="address" name="address" placeholder="e.g., 123 Main St, Lipa City" required>
                                    </div>
 <hr>

                                    <!-- JOB + PHOTO -->
                                    <h5 class="text-primary"><i class="fas fa-briefcase"></i> Occupation & Photo</h5>
                       
                                    <div class="form-group">
                                        <label for="occupation">What is the member’s occupation?</label>
                                        <input type="text" class="form-control" id="occupation" name="occupation" placeholder="e.g., Teacher, Engineer" required>
                                    </div>

                                    

                                    <div class="form-group">
                                        <label for="photo">Upload Member’s Photo (optional)</label>
                                        <input type="file" class="form-control" id="photo" name="photo">
                                    </div>
  <hr>

                                    <!-- PASSWORD SECTION -->
                             <h5 class="text-primary"><i class="fas fa-briefcase"></i> Password</h5>
                       <div class="form-group">
                                        <label for="password">Set a password for the member</label>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter a secure password" required>
                                    </div>
                                </div>
                                

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Save Member</button>
                                    <a href="/rotary/webpages/club-members/manage-members/manage_members.php" class="btn btn-success float-right">
                                        <i class="fas fa-eye"></i> View Members
                                    </a>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline-block">
            <b>Developed By</b> <a href="https://codeastro.com/">Group 9</a>
        </div>
    </footer>
</div>

<?php include('../../../includes/footer.php'); ?>

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
        Are you sure you want to add this member?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">No, Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmSubmit">Yes, Update</button>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript Dependencies -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Scripts -->
<script>
    function isAtLeast18YearsOld(dobValue) {
        const dob = new Date(dobValue);
        const today = new Date();
        const age = today.getFullYear() - dob.getFullYear();
        const month = today.getMonth() - dob.getMonth();
        return (age > 18 || (age === 18 && (month > 0 || (month === 0 && today.getDate() >= dob.getDate()))));
    }

    function showClientValidationError(message) {
        $('#clientValidationMessage').text(message);
        $('#clientValidationAlert').fadeIn();
    }

    $(document).ready(function () {
        const form = $('#editProfileForm');

        form.on('submit', function (e) {
            const dobValue = $('#dob').val();
            const contact = $('#contactNumber').val();
            const phoneRegex = /^09\d{9}$/;

            $('#clientValidationAlert').hide();

            if (!isAtLeast18YearsOld(dobValue)) {
                e.preventDefault();
                showClientValidationError("You must be at least 18 years old.");
                return;
            }

            if (!phoneRegex.test(contact)) {
                e.preventDefault();
                showClientValidationError("Contact number must start with 09 and be exactly 11 digits.");
                return;
            }

            if (!form.data('confirmed')) {
                e.preventDefault();
                $('#confirmModal').modal('show');
            }
        });

        $('#confirmSubmit').on('click', function () {
            $('#confirmModal').modal('hide');
            $('#editProfileForm').data('confirmed', true).submit();
        });
    });
</script>

</body>
</html>
