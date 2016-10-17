<?php
  // 1. Create a database connection


	$dbhost='108.179.248.219';
	$dbuser='isacoint_fueled';
	$dbpass='Dania412!';
	$dbname='isacoint_welcome';

  $connection = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
  // Test if connection succeeded
  if(mysqli_connect_errno()) {
    die("Database connection failed: " . 
         mysqli_connect_error() . 
         " (" . mysqli_connect_errno() . ")"
    );
  }
?>
<?php
	// 2. Perform database query
	//     $query  = "SELECT yearweek(subTIME(created_at,'0 4:00:00.0'),1) AS week, count(increment_id) ";
	//     $query .= "AS num_orders, round(sum(grand_total),2) AS daily_total, min(increment_id) as min2, max(increment_id) as max2 ";
	//     $query .= "FROM sales_flat_order ";
	//     $query .= "WHERE yearweek(subTIME(created_at,'0 4:00:00.0'),1) = yearweek(SUBTIME(current_timestamp,'0 4:00:00.0'),1) ";
	//     $query .= "GROUP BY yearweek(subTIME(created_at,'0 4:00:00.0'),1)";
	// 	   $result = mysqli_query($connection, $query);
	
	$query  = "SELECT * ";
    $query .= "FROM sales_flat_order ";
    $query .= "ORDER BY increment_id DESC";
	$result = mysqli_query($connection, $query);
	// Test if there was a query error
	if (!$result) {
		die("Database query failed.");
	}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html lang="en">
	<head>
		<title>Databases</title>
	</head>
	<body>
		
		<?php
			// 3. Use returned data (if any)
			// while($order = mysqli_fetch_assoc($result)) {
                // output data from each row
                // echo '<pre>' . var_dump($row) . '</pre><hr>';
                // echo $order["increment_id"] . "<br>";
                // echo $order["created_at"] . "<hr>";
                // echo $row["coupon_code"] . "<br><hr>";
			//}
		?>
        
        <?php
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $prev_date = date('Y-m-d', strtotime($date .' -1 day'));
        $next_date = date('Y-m-d', strtotime($date .' +1 day'));
        ?>

        <a href="?date=<?=$prev_date;?>">Previous</a>
        <a href="?date=<?=$next_date;?>">Next</a>
        

        <ul>
        <?php
        while($order = mysqli_fetch_assoc($result)) {
            ?>
            <li><?php echo $order["increment_id"] . "<br>"?></li>
            <li><?php echo $order["created_at"] . "<hr>";?></li>
            <?php
        }
		?>
        </ul>
        
        
        
		<?php
		  // 4. Release returned data
		  mysqli_free_result($result);
		?>
	</body>
</html>

<?php
  // 5. Close database connection
  mysqli_close($connection);
?>