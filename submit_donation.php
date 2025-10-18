<?php
// submit_donation.php
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

$donor_name = trim($_POST['donor_name'] ?? '');
$food_type  = trim($_POST['food_type'] ?? '');
$quantity   = intval($_POST['quantity'] ?? 0);
$expiry_date = trim($_POST['expiry_date'] ?? '');

if ($donor_name === '' || $food_type === '') {
    echo "<h2 style='color:red;text-align:center;'>Donor name and food type are required.</h2>";
    exit;
}

$expiry_sql = ($expiry_date === '') ? null : $expiry_date;

/* Insert into hotel_donations */
$stmt = $conn->prepare("INSERT INTO hotel_donations (donor_name, food_type, quantity, expiry_date, status) VALUES (?, ?, ?, ?, 'Pending')");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("ssis", $donor_name, $food_type, $quantity, $expiry_sql);
$ok = $stmt->execute();
if (!$ok) {
    die("Insert hotel_donations failed: " . $stmt->error);
}
$donation_id = $conn->insert_id;
$stmt->close();

/* Mirror into admin_donations if table exists */
$res = $conn->query("SHOW TABLES LIKE 'admin_donations'");
if ($res && $res->num_rows > 0) {
    $insert_ok = false;
    $sql1 = "INSERT INTO admin_donations (donation_id, donor_name, food_type, quantity, expiry_date, status) VALUES (?, ?, ?, ?, ?, 'Pending')";
    $s1 = $conn->prepare($sql1);
    if ($s1) {
        $s1->bind_param("issis", $donation_id, $donor_name, $food_type, $quantity, $expiry_sql);
        $insert_ok = $s1->execute();
        $s1->close();
    }
    if (!$insert_ok) {
        $sql2 = "INSERT INTO admin_donations (donor_name, food_type, quantity, expiry_date, status) VALUES (?, ?, ?, ?, 'Pending')";
        $s2 = $conn->prepare($sql2);
        if ($s2) {
            $s2->bind_param("ssis", $donor_name, $food_type, $quantity, $expiry_sql);
            $s2->execute();
            $s2->close();
        }
    }
}

/* After successful insert show simple success */
echo '
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Donation Received</title></head>
<body style="font-family:Arial,Helvetica,sans-serif;text-align:center;padding:40px;">
  <h2>Donation Received</h2>
  <p>Thank you, <strong>' . htmlspecialchars($donor_name) . '</strong> — Donation ID: <strong>' . intval($donation_id) . '</strong></p>
  <p><a href="donate_form.html">Back to donation form</a> | <a href="login.html">Home</a></p>
</body>
</html>
';

$conn->close();
?>
