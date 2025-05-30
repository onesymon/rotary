<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/rotary/includes/config.php');

// Currency symbol
$currencySymbol = '₱';
$currencyQuery = "SELECT currency FROM settings WHERE id = 1";
$currencyResult = $conn->query($currencyQuery);
if ($currencyResult->num_rows > 0) {
    $currencySymbol = $currencyResult->fetch_assoc()['currency'];
}

$data = json_decode(file_get_contents("php://input"), true);
$member_ids = $data['member_ids'] ?? [];
$report_type = $data['report_type'] ?? 'monthly';

$html = '';

foreach ($member_ids as $id) {
    $stmt = $conn->prepare("SELECT m.*, p.position_name FROM members m LEFT JOIN club_position p ON m.role = p.id WHERE m.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();

    if (!$member) continue;

    $html .= "<div class='card mb-4 shadow-sm'>";
    $html .= "<div class='card-header text-center py-2'>";
    $html .= "<h3 class='mb-0'>" . htmlspecialchars($member['fullname']) . "</h3>";
    $html .= "<small class='text-secondary'>Membership Report</small>";
    $html .= "</div>";

    $html .= "<div class='card-body p-3' id='member-report-{$member['id']}'>";

    // Member Info Compact Panel
    $html .= "<div class='d-flex justify-content-between flex-wrap mb-3 small text-muted'>";
    $html .= "<div><strong>Email:</strong> " . htmlspecialchars($member['email']) . "</div>";
    $html .= "<div><strong>Contact:</strong> " . htmlspecialchars($member['contact_number']) . "</div>";
    $html .= "<div><strong>Position:</strong> " . htmlspecialchars($member['position_name'] ?? 'N/A') . "</div>";
    $html .= "<div><strong>Joined:</strong> " . date("M j, Y", strtotime($member['created_at'])) . "</div>";
    $html .= "</div>";

    // Transaction History
    $stmt2 = $conn->prepare("SELECT * FROM club_transactions WHERE member_id = ? ORDER BY transaction_date DESC LIMIT 8");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $transactions = $stmt2->get_result();

    $html .= "<h5 class='border-bottom pb-1 mb-2'>Transaction History</h5>";
    if ($transactions->num_rows > 0) {
        $html .= "<table class='table table-sm table-striped mb-3'>";
        $html .= "<thead class='thead-light'><tr>";
        $html .= "<th class='text-nowrap'>Date</th><th class='text-right'>Amount</th><th>Reference #</th>";
        $html .= "</tr></thead><tbody>";

        while ($row = $transactions->fetch_assoc()) {
            $html .= "<tr>";
            $html .= "<td class='text-nowrap'>" . date("M j, Y", strtotime($row['transaction_date'])) . "</td>";
            $html .= "<td class='text-right'>{$currencySymbol} " . number_format($row['amount'], 2) . "</td>";
            $html .= "<td>" . htmlspecialchars($row['reference_number']) . "</td>";
            $html .= "</tr>";
        }

        $html .= "</tbody></table>";
    } else {
        $html .= "<p class='text-muted small mb-3'>No transactions found.</p>";
    }

    // Total Contributions Summary
    $stmt3 = $conn->prepare("SELECT SUM(amount) as total, COUNT(*) as txn_count FROM club_transactions WHERE member_id = ?");
    $stmt3->bind_param("i", $id);
    $stmt3->execute();
    $totalData = $stmt3->get_result()->fetch_assoc();
    $total = $totalData['total'] ?? 0;
    $txnCount = $totalData['txn_count'] ?? 0;

    $html .= "<div class='mb-3'>";
    $html .= "<strong>Total Contributions:</strong> ";
    $html .= "<span class='h5 text-primary'>{$currencySymbol} " . number_format($total, 2) . "</span>";
    $html .= " <small class='text-muted'>({$txnCount} transactions)</small>";
    $html .= "</div>";

    // Attendance History
    $stmt5 = $conn->prepare("SELECT a.activity_id, COALESCE(e.title, p.title) AS title, COALESCE(e.event_date, p.start_date) AS date 
                         FROM club_attendances a 
                         LEFT JOIN club_events e ON a.category = 'Club Event' AND a.activity_id = e.id 
                         LEFT JOIN club_projects p ON a.category = 'Club Project' AND a.activity_id = p.id 
                         WHERE a.member_id = ? ORDER BY date DESC LIMIT 8");
    $stmt5->bind_param("i", $id);
    $stmt5->execute();
    $attendances = $stmt5->get_result();

    $html .= "<h5 class='border-bottom pb-1 mb-2'>Recent Attendance</h5>";
    if ($attendances->num_rows > 0) {
        $html .= "<ul class='list-unstyled small mb-3'>";
        while ($row = $attendances->fetch_assoc()) {
            $date = $row['date'] ? date("M j, Y", strtotime($row['date'])) : 'N/A';
            $html .= "<li class='mb-1'>";
            $html .= "<strong>" . htmlspecialchars($row['title']) . "</strong>";
            $html .= " <span class='text-muted float-right'>{$date}</span>";
            $html .= "</li>";
        }
        $html .= "</ul>";
    } else {
        $html .= "<p class='text-muted small'>No recent attendance found.</p>";
    }

    // Export Buttons compact & center aligned
    $html .= "<div class='text-center'>";
    $html .= "<button class='btn btn-sm btn-outline-primary mr-2' onclick=\"downloadPDF('member-report-{$member['id']}', 'membership_report_{$member['id']}')\">";
    $html .= "<i class='fas fa-file-pdf'></i> PDF</button>";
    $html .= "<button class='btn btn-sm btn-outline-success' onclick=\"printSection('member-report-{$member['id']}')\">";
    $html .= "<i class='fas fa-print'></i> Print</button>";
    $html .= "</div>";

    $html .= "</div></div>";
}

$html .= "
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 0.9rem;
    }
    .card {
        border-radius: 0.4rem;
    }
    .table th, .table td {
        vertical-align: middle;
    }
    .text-nowrap {
        white-space: nowrap;
    }
    @media print {
        body * {
            visibility: hidden;
        }
        #printable-section, #printable-section * {
            visibility: visible;
        }
        #printable-section {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
    }
</style>

<script src='https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js'></script>
<script>
function downloadPDF(elementId, filename) {
    const element = document.getElementById(elementId);
    if (!element) return alert('Content not found for PDF export.');
    const opt = {
        margin: 0.4,
        filename: filename + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}

function printSection(id) {
    const content = document.getElementById(id);
    if (!content) return alert('Content not found for printing.');
    const printWindow = window.open('', '', 'width=900,height=700');
    printWindow.document.write('<html><head><title>Print Report</title>');
    printWindow.document.write('<link rel=\"stylesheet\" href=\"https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css\">');
    printWindow.document.write('<style>body{font-family: Arial, sans-serif; padding: 10px;}</style></head><body>');
    printWindow.document.write(content.innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
}
</script>
";

echo $html;
