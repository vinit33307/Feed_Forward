<?php
// admin.php
include 'db_config.php';
header('Content-Type: application/json');

$out = [
  'donations' => [],
  'admin_ngos' => [], 
  'admin_volenteer' => []
];

// --- Donations (always from hotel_donations to avoid duplicates) ---
$res = mysqli_query($conn, "
    SELECT donation_id, donor_name, food_type AS food_item,
           quantity, expiry_date, status
    FROM hotel_donations
    ORDER BY donation_id DESC
");

if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $out['donations'][] = $r;
    }
}



// --- NGOs (from admin_ngos if exists) ---
if ($conn->query("SHOW TABLES LIKE 'admin_ngos'")->num_rows > 0) {
  $r2 = mysqli_query($conn, "SELECT * FROM admin_ngos ORDER BY ngo_id DESC");
  if ($r2) while ($x = mysqli_fetch_assoc($r2)) $out['admin_ngos'][] = $x;
}

// --- Volunteers (from admin_volenteer if exists) ---
if ($conn->query("SHOW TABLES LIKE 'admin_volenteer'")->num_rows > 0) {
  $r3 = mysqli_query($conn, "SELECT * FROM admin_volenteer ORDER BY volunteer_id DESC");
  if ($r3) while ($v = mysqli_fetch_assoc($r3)) $out['admin_volenteer'][] = $v;
}

echo json_encode($out);
$conn->close();
?>

