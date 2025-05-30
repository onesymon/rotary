<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/rotary/includes/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
  header("Location: /rotary/webpages/logout/logout.php");
  exit();
}

// Counter parts
function getTotalMembersCount()
{
    global $conn;
    $query = "SELECT COUNT(*) AS total FROM members";
    $result = $conn->query($query);
    return $result->fetch_assoc()['total'] ?? 0;
}

function getPendingTransactionsCount()
{
    global $conn;
    $query = "SELECT COUNT(*) AS total FROM club_transactions WHERE payment_status = 'Pending'";
    $result = $conn->query($query);
    return $result->fetch_assoc()['total'] ?? 0;
}

function getExpiringSoonCount()
{
    global $conn;
    $query = "SELECT COUNT(*) AS total FROM members WHERE expiry_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 7 DAY";
    $result = $conn->query($query);
    return $result->fetch_assoc()['total'] ?? 0;
}

function getTotalRevenueWithCurrency()
{
    global $conn;
    $currency = '$';
    $currencyQuery = "SELECT currency FROM settings LIMIT 1";
    $currencyResult = $conn->query($currencyQuery);
    if ($currencyResult->num_rows > 0) {
        $currency = $currencyResult->fetch_assoc()['currency'];
    }

    $revenueQuery = "SELECT SUM(amount) AS total FROM club_transactions WHERE payment_status = 'Paid'";
    $revenueResult = $conn->query($revenueQuery);
    $total = $revenueResult->fetch_assoc()['total'] ?? 0;

    return $currency . number_format($total, 2);
}

function getFinishedProjectsThisMonth()
{
    global $conn;
    $query = "SELECT COUNT(*) AS finishedCount FROM club_projects WHERE status = 'completed' AND MONTH(end_date) = MONTH(CURRENT_DATE()) AND YEAR(end_date) = YEAR(CURRENT_DATE())";
    $result = $conn->query($query);
    return $result->fetch_assoc()['finishedCount'] ?? 0;
}

$fetchLogoQuery = "SELECT logo FROM settings WHERE id = 1";
$fetchLogoResult = $conn->query($fetchLogoQuery);
$logoPath = ($fetchLogoResult->num_rows > 0) ? $fetchLogoResult->fetch_assoc()['logo'] : '/rotary/dist/img/default-logo.png';
?>

<?php include('includes/header.php');?>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <?php include('includes/nav.php');?>
  <?php include('includes/sidebar.php');?>

  <div class="content-wrapper">
    <?php include('includes/page_title.php');?>

    <section class="content">
      <div class="container-fluid">

        <!-- Dashboard Widgets -->
        <div class="row g-3">
          <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-primary text-white">
              <div class="inner">
                <h4><?php echo getTotalMembersCount(); ?></h4>
                <p>Total Members</p>
              </div>
              <div class="icon"><i class="fas fa-users"></i></div>
            </div>
          </div>

          <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-warning text-white">
              <div class="inner">
                <h4><?php echo getExpiringSoonCount(); ?></h4>
                <p>Delinquent Members</p>
              </div>
              <div class="icon"><i class="fas fa-hourglass-half"></i></div>
            </div>
          </div>

          <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-success text-white">
              <div class="inner">
                <h4><?php echo getTotalRevenueWithCurrency(); ?></h4>
                <p>Total Fund</p>
              </div>
              <div class="icon"><i class="fas fa-coins"></i></div>
            </div>
          </div>

          <?php if ($_SESSION['role'] == 'officer'): ?>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="small-box bg-danger text-white">
              <div class="inner">
                <h4><?php echo getPendingTransactionsCount(); ?></h4>
                <p>Pending Transactions</p>
              </div>
              <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
            </div>
          </div>
          <?php endif; ?>

          <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm border-0">
              <div class="card-header bg-info text-white text-center rounded-top">
                <h5>Finished Projects (This Month)</h5>
              </div>
              <div class="card-body bg-light">
                <div class="row">
                  <div class="col-4 text-center">
                    <img src="/rotary/dist/img/project1.jpg" class="img-fluid rounded" alt="Project 1">
                  </div>
                  <div class="col-4 text-center">
                    <img src="/rotary/dist/img/project2.jpg" class="img-fluid rounded" alt="Project 2">
                  </div>
                  <div class="col-4 text-center">
                    <img src="/rotary/dist/img/project3.jpg" class="img-fluid rounded" alt="Project 3">
                  </div>
                </div>
                <div class="text-center mt-3">
                  <a href="/rotary/webpages/projects/projects.php" class="btn btn-sm btn-outline-info">View All</a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Achievements -->
        <div class="row mt-4">
          <div class="col-12">
            <div class="card shadow-sm">
              <div class="card-header bg-gradient-primary text-white">
                <h4 class="mb-0">🏆 Rotary Achievements</h4>
              </div>
              <div class="card-body bg-white">
                <div class="row text-center">
                  <div class="col-md-4">
                    <i class="fas fa-user-tie fa-2x text-primary"></i>
                    <h6 class="mt-2 font-weight-bold">Best Rotarian</h6>
                    <p>John Doe</p>
                  </div>
                  <div class="col-md-4">
                    <i class="fas fa-hand-holding-usd fa-2x text-success"></i>
                    <h6 class="mt-2 font-weight-bold">Most Donations</h6>
                    <p>Mary Smith</p>
                  </div>
                  <div class="col-md-4">
                    <i class="fas fa-handshake fa-2x text-warning"></i>
                    <h6 class="mt-2 font-weight-bold">Most Meetings Attended</h6>
                    <p>Robert Johnson</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Calendar -->
        <div class="row mt-4">
          <div class="col-12">
            <div class="card shadow-sm">
              <div class="card-header bg-info text-white">
                <h4 class="mb-0">📅 Project Calendar</h4>
              </div>
              <div class="card-body bg-white">
                <div id="project-calendar"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- FullCalendar CDN -->
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
          const calendarEl = document.getElementById('project-calendar');
          const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 600,
            headerToolbar: {
              left: 'prev,next today',
              center: 'title',
              right: 'dayGridMonth,timeGridWeek,listMonth'
            },
            events: [
              <?php
              $projectsQuery = "SELECT id, title, start_date, end_date, status FROM club_projects";
              $projectsResult = $conn->query($projectsQuery);
              while ($project = $projectsResult->fetch_assoc()) {
                  $title = htmlspecialchars($project['title'], ENT_QUOTES);
                  $start = $project['start_date'];
                  $end = $project['end_date'] ?? $project['start_date'];
                  $status = $project['status'];
                  $color = match ($status) {
                      'planned' => '#007bff',
                      'ongoing' => '#28a745',
                      'completed' => '#6c757d',
                      'cancelled' => '#dc3545',
                      default => '#17a2b8'
                  };
                  echo "{
                      title: '{$title}',
                      start: '{$start}',
                      end: '{$end}',
                      backgroundColor: '{$color}',
                      borderColor: '{$color}',
                      url: '/rotary/webpages/projects/project_details.php?id={$project['id']}'
                  },";
              }
              ?>
            ]
          });
          calendar.render();
        });
        </script>

      </div>
    </section>
  </div>

  <footer class="main-footer">
    <div class="float-right d-none d-sm-inline-block">
      <b>Developed By</b> <a href="#">Group 9</a>
    </div>
  </footer>
</div>

<?php include('includes/footer.php');?>
</body>
</html>
