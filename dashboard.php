<?php
// ==========================
// NGO Dashboard (FeedForward)
// ==========================

// Database connection
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "FeedForward"; // ✅ your database name

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle Accept/Reject POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $action = $_POST['action'];

    $stmt = $conn->prepare("UPDATE pickup SET status = ? WHERE pickup_id = ?");
    $stmt->bind_param("si", $action, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Donation status updated successfully!'); window.location.href='dashboard.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error updating status');</script>";
    }
    $stmt->close();
}

// Fetch all donation records
$result = $conn->query("SELECT * FROM pickup ORDER BY pickup_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>FeedForward | NGO Dashboard</title>
<style>
    body { font-family: Arial, sans-serif; background: #e6f7f2; margin: 0; padding: 0; text-align: center; }
    h2 { background: #4fd0cc; color: white; padding: 15px 0; margin: 0; font-size: 24px; }
    table { margin: 30px auto; border-collapse: collapse; width: 90%; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: white; border-radius: 8px; overflow: hidden; }
    th, td { padding: 12px 10px; border: 1px solid #ddd; font-size: 15px; text-align: center; }
    th { background: #047857; color: white; text-transform: uppercase; }
    tr:nth-child(even) { background: #f8fdfb; }
    button { padding: 6px 12px; margin: 2px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; color: white; }
    .accept { background-color: #10b981; }
    .reject { background-color: #ef4444; }
    .accept:hover { background-color: #059669; }
    .reject:hover { background-color: #b91c1c; }
</style>
</head>
<body>
<h2>🍽 FeedForward NGO Dashboard</h2>

<table>
<tr>
    <th>ID</th>
    <th>Donor Name</th>
    <th>Food Item</th>
    <th>Quantity</th>
    <th>Expiry Date</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['pickup_id'] ?></td>
            <td><?= htmlspecialchars($row['donor_name']) ?></td>
            <td><?= htmlspecialchars($row['food_item']) ?></td>
            <td><?= htmlspecialchars($row['quantity']) ?></td>
            <td><?= htmlspecialchars($row['expiry_date']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td>
                <?php if ($row['status'] === 'Pending'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $row['pickup_id'] ?>">
                        <button class="accept" name="action" value="Accepted">Accept</button>
                        <button class="reject" name="action" value="Rejected">Reject</button>
                    </form>
                <?php else: ?>
                    <span style="color:gray;"><?= $row['status'] ?></span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="7">No donations found.</td></tr>
<?php endif; ?>
</table>

</body>
</html>
<?php $conn->close(); ?>