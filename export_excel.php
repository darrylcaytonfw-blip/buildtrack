<?php
/* export_excel.php */
include 'database.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=buildtrack_report.xls");

$result = $conn->query("
    SELECT project_name, location, owner, subject
    FROM projects
");

echo "<table border='1'>";
echo "<tr>
        <th>Project</th>
        <th>Location</th>
        <th>Owner</th>
        <th>Subject</th>
      </tr>";

while($row = $result->fetch_assoc()){
    echo "<tr>
            <td>{$row['project_name']}</td>
            <td>{$row['location']}</td>
            <td>{$row['owner']}</td>
            <td>{$row['subject']}</td>
          </tr>";
}
echo "</table>";
?>