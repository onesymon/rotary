<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/rotary/includes/config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /rotary/webpages/logout/login.php");
    exit();
}

if ($_SESSION['role'] !== '1' && $_SESSION['role'] !== '100') {
    header("Location: /rotary/dashboard.php");
    exit();
}

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateSettings'])) {
    $systemName = $_POST['systemName'];
    $currency = $_POST['currency'];

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logoName = basename($_FILES['logo']['name']);
        $logoTmpName = $_FILES['logo']['tmp_name'];
        $uploadPath = '../../uploads/';
        $targetPath = $uploadPath . $logoName;

        if (move_uploaded_file($logoTmpName, $targetPath)) {
            $updateSettingsQuery = "UPDATE settings SET system_name = '$systemName', logo = '$logoName', currency = '$currency' WHERE id = 1";
        } else {
            $errorMessage = 'Error moving uploaded file.';
            $messageSource = 'settings';
        }
    } else {
        $updateSettingsQuery = "UPDATE settings SET system_name = '$systemName', currency = '$currency' WHERE id = 1";
    }

    if (isset($updateSettingsQuery)) {
        $updateSettingsResult = $conn->query($updateSettingsQuery);
        if ($updateSettingsResult) {
            $successMessage = 'System settings updated successfully.';
            $messageSource = 'settings';
        } else {
            $errorMessage = 'Error updating system settings: ' . $conn->error;
            $messageSource = 'settings';
        }
    }
}

// Handle Add Role// Handle Add Role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addRole'])) {
    $newRole = trim($_POST['position_name']);
    $messageSource = 'roles';

    if (!empty($newRole)) {
        // Check if role already exists (case-insensitive)
        $checkStmt = $conn->prepare("SELECT id FROM club_position WHERE LOWER(position_name) = LOWER(?)");
        $checkStmt->bind_param("s", $newRole);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $errorMessage = 'This role already exists.';
        } else {
            // Now safe to insert
            $stmt = $conn->prepare("INSERT INTO club_position (position_name) VALUES (?)");
            $stmt->bind_param("s", $newRole);
            if ($stmt->execute()) {
                $successMessage = 'New role added successfully.';
            } else {
                $errorMessage = 'Error adding new role: ' . $stmt->error;
            }
            $stmt->close();
        }

        $checkStmt->free_result(); // just to be clean
        $checkStmt->close();
    } else {
        $errorMessage = 'Please enter a valid role name.';
    }
}


// Handle Delete Role
if (isset($_GET['delete_role_id'])) {
    $roleId = intval($_GET['delete_role_id']);
    $deleteQuery = $conn->prepare("DELETE FROM club_position WHERE id = ?");
    $deleteQuery->bind_param("i", $roleId);
    if ($deleteQuery->execute()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=1");
        exit();
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=0");
        exit();
    }
}
if (isset($_GET['deleted'])) {
    if ($_GET['deleted'] == 1) {
        $successMessage = 'Role deleted successfully.';
        $messageSource = 'roles';
    } else {
        $errorMessage = 'Error deleting role.';
        $messageSource = 'roles';
    }
}
 
// Clear the ?deleted=1 URL parameter using JavaScript after message shows
if (isset($_GET['deleted'])) {
    echo "<script>
        if (window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.delete('deleted');
            window.history.replaceState({}, document.title, url.pathname);
        }
    </script>";
}
 


// Fetch Settings
$fetchSettingsQuery = "SELECT * FROM settings WHERE id = 1";
$fetchSettingsResult = $conn->query($fetchSettingsQuery);
if ($fetchSettingsResult->num_rows > 0) {
    $settings = $fetchSettingsResult->fetch_assoc();
}

// Fetch Roles
$rolesResult = $conn->query("SELECT * FROM club_position WHERE id != 100 ORDER BY id ASC");

include('../../includes/header.php');
?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
    <?php include('../../includes/nav.php'); ?>
    <?php include('../../includes/sidebar.php'); ?>

    <div class="content-wrapper">
        <?php include('../../includes/page_title.php'); ?>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">

                        <!-- System Settings -->
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-cogs"></i> System Settings</h3>
                            </div> 
                            <?php
                         if (!empty($successMessage) && $messageSource === 'settings') {
    echo '<div class="alert alert-success">' . $successMessage . '</div>';
    unset($successMessage, $messageSource);
} elseif (!empty($errorMessage) && $messageSource === 'settings') {
    echo '<div class="alert alert-danger">' . $errorMessage . '</div>';
    unset($errorMessage, $messageSource);
}

                            ?>

                            <form method="post" action="" enctype="multipart/form-data" id="settingsForm">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="systemName">System Name:</label>
                                        <input type="text" id="systemName" name="systemName" class="form-control" value="<?php echo $settings['system_name'] ?? ''; ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="logo">Logo:</label>
                                        <input type="file" id="logo" name="logo" class="form-control">
                                    </div>

                                    <div class="form-group">
                                        <label for="currency">Currency:</label>
                                        <input type="text" id="currency" name="currency" class="form-control" value="<?php echo $settings['currency'] ?? ''; ?>" required>
                                    </div>

                                    <button type="button" class="btn btn-primary confirm-action"
                                            data-action-type="submit-form"
                                            data-form-id="settingsForm"
                                            data-title="Update System Settings"
                                            data-message="Are you sure you want to update the system settings?">
                                        Update Settings
                                    </button>
                                    <input type="hidden" name="updateSettings" value="1">
                                </div>
                            </form>
                        </div>

                        <!-- Manage Roles -->
                        <div class="card card-primary mt-4">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-user-tag"></i> Manage Roles / Positions</h3>
                            </div>
  <?php
                            if (!empty($successMessage) && $messageSource === 'roles') {
    echo '<div class="alert alert-success">' . $successMessage . '</div>';
    unset($successMessage, $messageSource);
} elseif (!empty($errorMessage) && $messageSource === 'roles') {
    echo '<div class="alert alert-danger">' . $errorMessage . '</div>';
    unset($errorMessage, $messageSource);
}

                                ?>
                            <div class="card-body"> 
                               
                              

                                <form method="post" action="" id="addRoleForm">
                                    <div class="form-group">
                                        <label for="position_name">Add New Role:</label>
                                        <input type="text" id="position_name" name="position_name" class="form-control" placeholder="e.g., Treasurer, Auditor, Secretary" required>
                                    </div>
                                    <button type="button" class="btn btn-primary confirm-action"
                                            data-action-type="submit-form"
                                            data-form-id="addRoleForm"
                                            data-title="Add New Role"
                                            data-message="Are you sure you want to add this role?">
                                        Add Role
                                    </button>
                                    <input type="hidden" name="addRole" value="1">
                                </form>

                                <hr>

                                <h5>Available Roles:</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Role Name</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $rolesResult->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['position_name']); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-sm confirm-action"
                                                                data-action-type="redirect"
                                                                data-title="Delete Role"
                                                                data-message="Are you sure you want to delete this role?"
                                                                data-url="?delete_role_id=<?php echo $row['id']; ?>">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline-block">
            <b>Developed By</b> <a href="#">Group 9</a>
        </div>
    </footer>
</div>

<!-- Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="confirmModalLabel">Confirm Action</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="confirmModalBody">
        Are you sure you want to perform this action?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" id="confirmModalProceedBtn" class="btn btn-primary">Yes, Proceed</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let confirmActionType = '';
    let confirmFormId = '';
    let confirmUrl = '';

    $(document).ready(function () {
        $('.confirm-action').on('click', function () {
            const actionType = $(this).data('action-type');
            const title = $(this).data('title') || 'Confirm Action';
            const message = $(this).data('message') || 'Are you sure?';
            const formId = $(this).data('form-id') || '';
            const url = $(this).data('url') || '';

            $('#confirmModalLabel').text(title);
            $('#confirmModalBody').html(message);

            confirmActionType = actionType;
            confirmFormId = formId;
            confirmUrl = url;

            $('#confirmModal').modal('show');
        });

        $('#confirmModalProceedBtn').on('click', function () {
            if (confirmActionType === 'submit-form' && confirmFormId) {
                document.getElementById(confirmFormId).submit();
            } else if (confirmActionType === 'redirect' && confirmUrl) {
                window.location.href = confirmUrl;
            }
        });
    });
</script>

<?php include('../../includes/footer.php'); ?>
</body>
</html>
