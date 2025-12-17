<?php
include 'assets/conn.php'; // Import Database Connection File

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_ids']) && is_array($_POST['employee_ids'])) {
    $employee_ids = $_POST['employee_ids'];

    // Filter and sanitize: keep only numeric values
    $employee_ids = array_filter($employee_ids, fn($id) => is_numeric($id));

    if (count($employee_ids) === 0) {
        echo "<script>alert('No valid employee IDs provided.'); window.location='view_employees.php';</script>";
        exit();
    }

    // Build placeholders (?, ?, ?, ...)
    $placeholders = implode(',', array_fill(0, count($employee_ids), '?'));
    $types = str_repeat('i', count($employee_ids)); // all are integers

    $sql = "DELETE FROM employee WHERE employee_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo "<script>alert('Database error: prepare failed.'); window.location='view_employees.php';</script>";
        exit();
    }

    $stmt->bind_param($types, ...$employee_ids);

    if ($stmt->execute()) {
        header('Location: view_employees.php?deleted=1');
        exit();
    } else {
        header('Location: view_employees.php?msg=Error deleting employee(s).');
        exit();
    }
} else {
    echo "<script>alert('No employees selected for deletion.'); window.location='view_employees.php';</script>";
    exit();
}
?>
