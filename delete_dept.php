<?php
include 'assets/conn.php'; // Include the Database Connection File

if (isset($_GET['dept_ids'], $_GET['scl_id']) && !empty($_GET['dept_ids']) && !empty($_GET['scl_id'])) {
    $departmentIds = explode(',', $_GET['dept_ids']);
    $schoolId = intval($_GET['scl_id']);

    // Validate all department IDs as integers
    $departmentIds = array_filter($departmentIds, function ($id) {
        return is_numeric($id);
    });

    if (!empty($departmentIds)) {
        // Build dynamic placeholders and bind types
        $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));
        $types = str_repeat('i', count($departmentIds));

        // Prepare the SQL query with IN clause
        $sql = "DELETE FROM departments WHERE department_id IN ($placeholders)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Dynamically bind the values
            $stmt->bind_param($types, ...array_map('intval', $departmentIds));

            if ($stmt->execute()) {
                header("Location: view_dept.php?id=$schoolId&msg=Departments Deleted Successfully");
                exit();
            } else {
                header("Location: view_dept.php?id=$schoolId&msg=Error Deleting Departments");
                exit();
            }
        } else {
            header("Location: view_dept.php?id=$schoolId&msg=Failed to prepare statement");
            exit();
        }
    } else {
        header("Location: view_dept.php?id=$schoolId&msg=Invalid Department IDs");
        exit();
    }
} else {
    // Fallback error redirection
    $schoolId = isset($_GET['scl_id']) ? intval($_GET['scl_id']) : 0;
    header("Location: view_dept.php?id=$schoolId&msg=Missing Required Parameters");
    exit();
}
?>
