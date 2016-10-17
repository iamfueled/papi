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

    $prev = -2;
    $next = +1;

    $sql = "SELECT created_at AS week, count(increment_id) 
                AS num_orders, round(sum(grand_total),2) AS daily_total, min(increment_id) as min2, max(increment_id) as max2
              FROM sales_flat_order
             WHERE yearweek(subTIME(created_at,'0 4:00:00.0'),1) between yearweek(SUBTIME(current_timestamp,'0 4:00:00.0'),1) $prev
               AND yearweek(SUBTIME(current_timestamp,'0 4:00:00.0'),1) $prev
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

    // add logic to button
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $prev_date = date('Y-m-d', strtotime($date .' -1 day'));
    $next_date = date('Y-m-d', strtotime($date .' +1 day'));
    ?>

    <a href="?date=<?=$prev_date;?>">Previous</a>
    <a href="?date=<?=$next_date;?>">Next</a>

    <?php // Button ?>

    <form>
    <input type="button" value="Add one" onclick="add();"/>
    <input type="button" value="Remove one" onclick="remove();"/>
    </form>
    <span id="field">0</span>

    <script>
    var value = 0;

    function add() {
        value++;
        document.getElementById("field").innerHTML = value;
    }
    function remove() {
        value--;
        document.getElementById("field").innerHTML = value;
    }
    </script>

    <?php
    $conn->close();
    ?> 
  </div>
  </div>

  <?php
      // figure out interval for the previous day and week 
        // -1 = 1 week

      // add a php variable in a MySQL statement

      // echo day sales between 1 day to the other
      // make $sql break and add a separate variable in place of the -4
      // make button to decrement the number and reload from the database
      
  ?>


</body>
</html>

