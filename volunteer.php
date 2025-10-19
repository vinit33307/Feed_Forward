<?php
// volunteer.php — GET for dashboard, POST for updating delivery type and delivery verification
include 'db_config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

/* ---------------- GET: Volunteer Dashboard Data ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT 
                distribution_id, 
                donation_id, 
                delivery_type, 
                status, 
                delivery_verified
            FROM ngo_distributions
            ORDER BY distribution_id DESC";
    $res = mysqli_query($conn, $sql);
    $out = [];
    if ($res) while ($r = mysqli_fetch_assoc($res)) $out[] = $r;
    echo json_encode($out);
    $conn->close();
    exit;
}

/* ---------------- POST: Volunteer Actions ---------------- */
$distribution_id = intval($_POST['distribution_id'] ?? 0);
$action = strtolower(trim($_POST['action'] ?? ''));
$delivery_type = trim($_POST['delivery_type'] ?? '');

if ($distribution_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid distribution ID']);
    exit;
}

/* --- SET DELIVERY TYPE --- */
if ($action === 'set_type') {
    if ($delivery_type === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Missing delivery type']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE ngo_distributions SET delivery_type=? WHERE distribution_id=?");
    $stmt->bind_param("si", $delivery_type, $distribution_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Delivery type set']);
    $conn->close();
    exit;
}

/* --- MARK AS DELIVERED --- */
if ($action === 'delivered') {
    $stmt = $conn->prepare("UPDATE ngo_distributions SET delivery_verified=1, status='Delivered' WHERE distribution_id=?");
    $stmt->bind_param("i", $distribution_id);
    $stmt->execute();
    $stmt->close();

    // Mirror in admin_volenteer if exists
    if ($conn->query("SHOW TABLES LIKE 'admin_volenteer'")->num_rows > 0) {
        $stmt2 = $conn->prepare("UPDATE admin_volenteer SET status='Delivered' WHERE EXISTS (
                                    SELECT 1 FROM ngo_distributions WHERE ngo_distributions.distribution_id = ?
                                )");
        $stmt2->bind_param("i", $distribution_id);
        $stmt2->execute();
        $stmt2->close();
    }

    echo json_encode(['success' => true, 'message' => 'Delivery verified successfully']);
    $conn->close();
    exit;
}
if ($action === 'in_transit') {
    $stmt = $conn->prepare("UPDATE ngo_distributions SET status='In Transit' WHERE distribution_id=?");
    $stmt->bind_param("i", $distribution_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success'=>true]);
    $conn->close();
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
$conn->close();
exit;
?>
