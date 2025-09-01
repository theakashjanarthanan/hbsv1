<?php
include 'assets/conn.php'; // Import Database Connection File

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['school_ids']) && is_array($_POST['school_ids'])) {
    $school_ids = $_POST['school_ids'];

    // Ensure all values are integers
    $school_ids = array_filter($school_ids, fn($id) => is_numeric($id));

    if (count($school_ids) === 0) {
        header("Location: view_school.php?msg=No valid school IDs provided.");
        exit();
    }

    // Prepare placeholders (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($school_ids), '?'));
    $types = str_repeat('i', count($school_ids)); // "i" for integer
    $sql = "DELETE FROM schools WHERE school_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        header("Location: view_school.php?msg=Database prepare failed.");
        exit();
    }

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$school_ids);

    if ($stmt->execute()) {
        header("Location: view_school.php?msg=Selected school(s) deleted successfully.");
        exit();
    } else {
        header("Location: view_school.php?msg=Error deleting selected school(s).");
        exit();
    }
} else {
    header("Location: view_school.php?msg=No schools selected for deletion.");
    exit();
}
?>
