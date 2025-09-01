<?php
include('assets/conn.php'); // Include your database connection

if (isset($_GET['query'])) {
    $query = $_GET['query'];

    // Fetch employees based on query (search for employee_name)
    $sql = "SELECT employee_id, employee_name, employee_email, employee_mobile FROM employee WHERE employee_name LIKE ?";
    $stmt = $conn->prepare($sql);
    $likeQuery = '%' . $query . '%';
    $stmt->bind_param("s", $likeQuery);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $employees = [];
        while ($row = $result->fetch_assoc()) {
            $employees[] = [
                'employee_id' => $row['employee_id'],
                'employee_email' => $row['employee_email'],
                'employee_mobile' => $row['employee_mobile'],
                'employee_name' => $row['employee_name']
            ];
        }

        // Return the list of employees as JSON
        echo json_encode($employees);
    } else {
        echo json_encode(['error' => 'Error fetching employees.']);
    }

    $stmt->close();
    $conn->close();
}
?>
