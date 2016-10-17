<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Papi Hub</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "papiinc1";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
     die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, firstname, lastname FROM MyGuests";
$sql = "SELECT yearweek(created_at) AS week, COUNT(increment_id) 
            AS num_orders, SUM(grand_total) AS daily_total
          FROM sales_flat_order
         WHERE yearweek(created_at) = yearweek('2015-08-30')
      GROUP BY yearweek(created_at)";
      // Use current_date in place of 2015-08-30

$result = $conn->query($sql);

if ($result->num_rows > 0) {
     // output data of each row
     while($row = $result->fetch_assoc()) {
        $week = substr($row["week"],4);
         echo "<br> Week ". $week . " - Number of Orders: ". $row["num_orders"]. " " . $row["daily_total"] . "Total " . " <br>";
     }
} else {
     echo "0 results";
}

$conn->close();
?> 

</body>
</html>