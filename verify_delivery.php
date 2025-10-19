<?php
// verify_delivery.php (updated)
include 'db_config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

$distribution_id = intval($_POST['distribution_id'] ?? 0);
if ($distribution_id <= 0) { http_response_code(400); echo json_encode(['error'=>'Invalid distribution id']); exit; }

// mark verified
$stmt = $conn->prepare("UPDATE ngo_distributions SET delivery_verified = 1, status = 'Delivered' WHERE distribution_id = ?");
$stmt->bind_param("i", $distribution_id);
$stmt->execute(); $stmt->close();

// get donation_id and volunteer_id
$s = $conn->prepare("SELECT donation_id, volunteer_id FROM ngo_distributions WHERE distribution_id = ?");
$s->bind_param("i",$distribution_id); $s->execute(); $s->bind_result($donation_id,$volunteer_id); $s->fetch(); $s->close();

// update admin_volenteer if exists (increment or mark)
if ($volunteer_id && $conn->query("SHOW TABLES LIKE 'admin_volenteer'")->num_rows>0) {
  $c = $conn->query("SHOW COLUMNS FROM admin_volenteer LIKE 'deliveries_count'");
  if ($c && $c->num_rows>0) {
    $u = $conn->prepare("UPDATE admin_volenteer SET deliveries_count = COALESCE(deliveries_count,0)+1 WHERE volunteer_id = ?");
    $u->bind_param("i",$volunteer_id); $u->execute(); $u->close();
  } else {
    $u2 = $conn->prepare("UPDATE admin_volenteer SET status='Delivered' WHERE volunteer_id = ?");
    $u2->bind_param("i",$volunteer_id); $u2->execute(); $u2->close();
  }
}

// mark admin_donations.status = 'Donated' if table exists and donation_id available
if (!empty($donation_id) && $conn->query("SHOW TABLES LIKE 'admin_donations'")->num_rows>0) {
  $upd = $conn->prepare("UPDATE admin_donations SET status='Donated' WHERE donation_id = ?");
  $upd->bind_param("i",$donation_id); $upd->execute(); $upd->close();
}

// also update hotel_donations if exists
if (!empty($donation_id)) {
  $upd2 = $conn->prepare("UPDATE hotel_donations SET status='Donated' WHERE donation_id = ?");
  $upd2->bind_param("i",$donation_id); $upd2->execute(); $upd2->close();
}

echo json_encode(['success'=>true,'distribution_id'=>$distribution_id]);
$conn->close();
?>
