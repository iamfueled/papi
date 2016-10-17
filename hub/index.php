<!doctype html>

<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Papi Hub</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<body>
  <div class="main">
    <div class="search">
      <center>
    <?php
      $servername='108.179.248.219';
      $username='isacoint_fueled';
      $password='Dania412!';
      $dbname='isacoint_welcome';

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
         die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT yearweek(subTIME(created_at,'0 4:00:00.0'),1) AS week, count(increment_id) 
                AS num_orders, round(sum(grand_total),2) AS daily_total, min(increment_id) as min2, max(increment_id) as max2
              FROM sales_flat_order
             WHERE yearweek(subTIME(created_at,'0 4:00:00.0'),1) = yearweek(SUBTIME(current_timestamp,'0 4:00:00.0'),1)
          GROUP BY yearweek(subTIME(created_at,'0 4:00:00.0'),1)";
          // Use current_date in place of 2015-08-30

    // $range = "SELECT
    //           FROM sales_flat_order
    //           WHERE yourDate >= DATEADD(wk, DATEDIFF(wk,0,GETDATE()), -1) -- Sunday
    //           AND yourDate <= DATEADD(wk, DATEDIFF(wk,0,GETDATE()), 5) -- Saturday"

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
         // output data of each row
         while($row = $result->fetch_assoc()) {
            echo "<h1>papiinc.com</h1>";
            setlocale(LC_MONETARY, 'en_US');

            $week = substr($row["week"],4);
            $total = $row["daily_total"];
            $dollars = money_format('%(#10n',$total) . "\n";
             echo "<h2>Week ". $week . "</h2><br><h1>" . $dollars . "</h1>" . $row["num_orders"] . " orders";
             // echo "<h2>Week ". $week . "</h2><br><h1>" . $dollars . "</h1>" . $row["num_orders"] . " orders" . " min: " . $row["min2"] . " max:" . $row{"max2"};
         }
    } else {
         echo "0 results";
    }

    $conn->close();
    ?> 
  </div>
  </div>
</body>
</html>

