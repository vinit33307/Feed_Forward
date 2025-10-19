<?php
// update_status.php
include 'db_config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error'=>'Method not allowed']);
  exit;
}

$donation_id = intval($_POST['donation_id'] ?? 0);
if ($donation_id <= 0) {
  http_response_code(400);
  echo json_encode(['error'=>'Invalid donation id']);
  exit;
}

$new_status = 'Accepted';

// update hotel_donations (if present)
$stmt = $conn->prepare("UPDATE hotel_donations SET status=? WHERE donation_id=?");
if ($stmt) { $stmt->bind_param("si",$new_status,$donation_id); $stmt->execute(); $stmt->close(); }

// admin_donations update (if exists)
if ($conn->query("SHOW TABLES LIKE 'admin_donations'")->num_rows > 0) {
  $stmt2 = $conn->prepare("UPDATE admin_donations SET status=? WHERE donation_id=?");
  if ($stmt2) { $stmt2->bind_param("si",$new_status,$donation_id); $stmt2->execute(); $stmt2->close(); }
}

// check if there is already a pickup for this donation (avoid duplicates).
$pickupExists = false;
$resCheck = $conn->query("SHOW COLUMNS FROM ngo_pickup LIKE 'donation_id'");
if ($resCheck && $resCheck->num_rows > 0) {
  $chk = $conn->prepare("SELECT pickup_id FROM ngo_pickup WHERE donation_id = ?");
  $chk->bind_param("i",$donation_id);
  $chk->execute();
  $chk->store_result();
  if ($chk->num_rows > 0) $pickupExists = true;
  $chk->close();
} else {
  // no donation_id column — try matching by donor_name & food_type
  $sub = $conn->prepare("SELECT donor_name, food_type FROM hotel_donations WHERE donation_id=?");
  $sub->bind_param("i",$donation_id);
  $sub->execute();
  $sub->bind_result($donor_name,$food_type);
  $found = $sub->fetch();
  $sub->close();
  if ($found) {
    $chk2 = $conn->prepare("SELECT pickup_id FROM ngo_pickup WHERE donor_name=? AND food_item=?");
    $chk2->bind_param("ss",$donor_name,$food_type);
    $chk2->execute();
    $chk2->store_result();
    if ($chk2->num_rows > 0) $pickupExists = true;
    $chk2->close();
  }
}

if ($pickupExists) {
  echo json_encode(['success'=>false,'message'=>'Pickup already exists for this donation']);
  $conn->close();
  exit;
}

// create pickup: fetch data first
$q = $conn->prepare("SELECT donor_name, food_type, quantity, expiry_date FROM hotel_donations WHERE donation_id=?");
$q->bind_param("i",$donation_id);
$q->execute();
$q->bind_result($donor_name,$food_type,$qty,$expiry);
$has = $q->fetch();
$q->close();

if (!$has) {
  echo json_encode(['success'=>false,'message'=>'Donation not found']);
  $conn->close();
  exit;
}

// insert into ngo_pickup — check whether donation_id column exists and insert accordingly
$colCheck = $conn->query("SHOW COLUMNS FROM ngo_pickup LIKE 'donation_id'");
if ($colCheck && $colCheck->num_rows > 0) {
  $ins = $conn->prepare("INSERT INTO ngo_pickup (donation_id, donor_name, food_item, quantity, expiry_date, status) VALUES (?, ?, ?, ?, ?, 'Waiting for NGO')");
  $ins->bind_param("issis",$donation_id,$donor_name,$food_type,$qty,$expiry);
  $ins->execute();
  $ins->close();
} else {
  $ins = $conn->prepare("INSERT INTO ngo_pickup (donor_name, food_item, quantity, expiry_date, status) VALUES (?, ?, ?, ?, 'Waiting for NGO')");
  $ins->bind_param("ssis",$donor_name,$food_type,$qty,$expiry);
  $ins->execute();
  $ins->close();
}

// optionally mirror admin_ngos if exists (insert minimal)
// optionally mirror admin_ngos if exists (insert minimal)
if ($conn->query("SHOW TABLES LIKE 'admin_ngos'")->num_rows > 0) {
    // insert a minimal row with placeholders so admin can see NGO list
    $stmt = $conn->prepare(
        "INSERT INTO admin_ngos (ngo_name, location, capacity)
         VALUES ('Awaiting NGO', 'TBD', 0)"
    );
    $stmt->execute();
    $stmt->close();
}


echo json_encode(['success'=>true,'status'=>$new_status]);
$conn->close();
exit;
?>
