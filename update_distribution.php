<?php
// update_distribution.php
include 'db_connect.php'; // $conn is mysqli object

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

// Expect donation_id, volunteer_id, delivery_type, status (pending/dispatched/delivered)
$donation_id = intval($_POST['donation_id'] ?? 0);
$volunteer_id = intval($_POST['volunteer_id'] ?? 0);
$delivery_type = $conn->real_escape_string($_POST['delivery_type'] ?? 'human');
$status = strtolower(trim($_POST['status'] ?? 'pending'));
$verified = ($status === 'delivered') ? 1 : 0;

if ($donation_id <= 0) {
    die("Invalid donation_id");
}

/* Upsert into ngo_distributions. We assume donation_id has UNIQUE constraint in ngo_distributions;
   if not, this will act as an insert. */
$sql = "INSERT INTO ngo_distributions (donation_id, volunteer_id, delivery_type, status, delivery_verified, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
          volunteer_id = VALUES(volunteer_id),
          delivery_type = VALUES(delivery_type),
          status = VALUES(status),
          delivery_verified = VALUES(delivery_verified),
          updated_at = NOW()";

$stmt = $conn->prepare($sql);
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->bind_param("iisis", $donation_id, $volunteer_id, $delivery_type, $status, $verified);
$execOk = $stmt->execute();
if (!$execOk) {
    die("Execute failed: " . $stmt->error);
}
$stmt->close();

/* Ensure admin_volenteer has an entry for this volunteer (insert if missing).
   Try to fetch volunteer details from volunteers table; otherwise insert minimal record. */
if ($volunteer_id > 0) {
    $res = $conn->query("SHOW TABLES LIKE 'admin_volenteer'");
    if ($res && $res->num_rows > 0) {
        // Check if volunteer already present in admin_volenteer by volunteer_id
        $check = $conn->prepare("SELECT volunteer_id FROM admin_volenteer WHERE volunteer_id = ?");
        $check->bind_param("i", $volunteer_id);
        $check->execute();
        $check->store_result();
        $exists = ($check->num_rows > 0);
        $check->close();

        if (!$exists) {
            // Try to fetch details from volunteers (if exists)
            $vname = null; $vemail = null; $vphone = null;
            $res2 = $conn->query("SHOW TABLES LIKE 'volunteers'");
            if ($res2 && $res2->num_rows > 0) {
                $s = $conn->prepare("SELECT name, email, phone FROM volunteers WHERE volunteer_id = ?");
                if ($s) {
                    $s->bind_param("i", $volunteer_id);
                    $s->execute();
                    $s->bind_result($vn, $ve, $vp);
                    if ($s->fetch()) {
                        $vname = $vn; $vemail = $ve; $vphone = $vp;
                    }
                    $s->close();
                }
            }

            if (!$vname) $vname = "Volunteer {$volunteer_id}";
            // Insert into admin_volenteer
            $ins = $conn->prepare("INSERT INTO admin_volenteer (volunteer_id, name, email, phone, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
            if ($ins) {
                $ins->bind_param("isss", $volunteer_id, $vname, $vemail, $vphone);
                $ins->execute();
                $ins->close();
            }
        } else {
            // Optionally update last activity or status; for now nothing
        }
    }
}

/* Update admin_ngos to mark distribution if admin_ngos exists and donation_id matches */
$res = $conn->query("SHOW TABLES LIKE 'admin_ngos'");
if ($res && $res->num_rows > 0) {
    // Try to locate admin_ngos row with donation_id and update status to reflect distribution
    $upd = $conn->prepare("UPDATE admin_ngos SET status = ?, distributed_at = NOW() WHERE donation_id = ?");
    if ($upd) {
        $upd->bind_param("si", $status, $donation_id);
        $upd->execute();
        $upd->close();
    }
}

/* Redirect back to volunteer dashboard or show a message */
header("Location: volunteer_dash.html");
exit;
?>
