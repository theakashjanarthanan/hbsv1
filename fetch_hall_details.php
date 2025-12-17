<?php
header('Content-Type: application/json');
require_once __DIR__ . '/assets/conn.php';

$hallId = isset($_GET['hall_id']) ? intval($_GET['hall_id']) : 0;
if ($hallId <= 0) {
	echo json_encode([ 'error' => 'Invalid hall_id' ]);
	exit;
}

$sql = "
    SELECT 
        h.hall_id,
        h.hall_name,
        h.capacity,
        h.type_id,
        h.school_id,
        h.department_id,
        h.section_id,
        s.school_name,
        d.department_name
    FROM hall_details h
    LEFT JOIN schools s ON s.school_id = h.school_id
    LEFT JOIN departments d ON d.department_id = h.department_id
    WHERE h.hall_id = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
	echo json_encode([ 'error' => 'DB prepare failed' ]);
	exit;
}
$stmt->bind_param('i', $hallId);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $row = $res->fetch_assoc()) {
	$typeMap = [
		1 => 'Seminar Hall',
		2 => 'Auditorium',
		3 => 'Lecture Hall',
		4 => 'Complex'
	];
	$typeName = isset($typeMap[(int)$row['type_id']]) ? $typeMap[(int)$row['type_id']] : 'Hall';
	$data = [
		'hall_id' => (int)$row['hall_id'],
		'hall_name' => $row['hall_name'],
		'capacity' => (int)$row['capacity'],
		'type_id' => (int)$row['type_id'],
		'type_name' => $typeName,
		'school_name' => $row['school_name'] ?: '',
		'department_name' => $row['department_name'] ?: ''
	];
	echo json_encode([ 'data' => $data ]);
} else {
	echo json_encode([ 'error' => 'Hall not found' ]);
}
$stmt->close();
?>


