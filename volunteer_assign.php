<?php
// volunteer_assign.php
// Handles assigning a volunteer to a distributed donation

include 'db_config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
  http_response_code(405); 
  echo json_encode(['error'=>'Method not allowed']); 
  exit; 
}

$distribution_id = intval($_POST['distribution_id'] ?? 0);
$volunteer_id = intval($_POST['volunteer_id'] ?? 0);

if ($distribution_id <= 0) { 
  http_response_code(400); 
  echo json_encode(['error'=>'Invalid distribution id']); 
  exit; 
}

// If volunteer_id not provided, create or use a placeholder volunteer
if ($volunteer_id <= 0) {
    if ($conn->query("SHOW TABLES LIKE 'volunteers'")->num_rows > 0) {
        // Volunteers table exists — add a temp volunteer
        $ins = $conn->prepare("INSERT INTO volunteers (name) VALUES ('Volunteer')");
        $ins->execute();
        $volunteer_id = $conn->insert_id;
        $ins->close();
    } elseif ($conn->query("SHOW TABLES LIKE 'admin_volenteer'")->num_rows > 0) {
        // Otherwise, create in admin_volenteer
        $ins = $conn->prepare("INSERT INTO admin_volenteer (name, status) VALUES ('Volunteer', 'Assigned')");
        $ins->execute();
        $volunteer_id = $conn->insert_id;
        $ins->close();
    } else {
        $volunteer_id = null;
    }
}

if ($volunteer_id) {
    // Update ngo_distributions table
    $stmt = $conn->prepare("UPDATE ngo_distributions SET volunteer_id = ?, status = 'Assigned' WHERE distribution_id = ?");
    $stmt->bind_param("ii", $volunteer_id, $distribution_id);
    $stmt->execute();
    $stmt->close();

    // Update or insert in admin_volenteer if table exists
    if ($conn->query("SHOW TABLES LIKE 'admin_volenteer'")->num_rows > 0) {
        $check = $conn->prepare("SELECT volunteer_id FROM admin_volenteer WHERE volunteer_id = ?");
        $check->bind_param("i", $volunteer_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // Update existing row
            $upd = $conn->prepare("UPDATE admin_volenteer SET status = 'Assigned' WHERE volunteer_id = ?");
            $upd->bind_param("i", $volunteer_id);
            $upd->execute();
            $upd->close();
        } else {
            // Insert new row if not found
            $ins2 = $conn->prepare("INSERT INTO admin_volenteer (volunteer_id, name, status) VALUES (?, 'Volunteer', 'Assigned')");
            $ins2->bind_param("i", $volunteer_id);
            $ins2->execute();
            $ins2->close();
        }
        $check->close();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Volunteer assigned successfully',
        'distribution_id' => $distribution_id,
        'volunteer_id' => $volunteer_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not create/assign volunteer']);
}

$conn->close();
?>
