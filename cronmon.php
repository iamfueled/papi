<?php
//Magento Cron script. GNU/GPL
//oliver.higgins@gmail.com
//provided without warranty or support
//================================================================
//insert your database info here
$server='108.179.248.219';
$user='isacoint_fueled';
$pass='Dania412!';
$db='isacoint_welcome';
//end data input
//================================================================

echo "<h1>Magento Cron Schedule</h1><h2>for ".$user."@".$server."</h2>";
//================================================================
//pending jobs

mysql_connect($server,$user,$pass);
@mysql_select_db($db) or die("Unable to select database");
//$query="SELECT * FROM cron_schedule" ;
$query='SELECT * FROM `cron_schedule` WHERE `status` ="pending" ORDER BY `scheduled_at` DESC' ;
$result=mysql_query($query);
$num=mysql_numrows($result);
echo "<h2>".$num." Jobs Pending</h2>";
echo '<table border="1"><tbody>';
echo "<tr><th>schedule_id</th><th>job_code</th><th>status</th><th>created_at</th><th>scheduled_at</th>";
//echo "<th>executed_at</th><th>finished_at</th></tr>";
$i=0;
while ($i < $num) {

$schedule_id=mysql_result ($result,$i,"schedule_id");                                      
$job_code=  mysql_result($result,$i,"job_code");
$status=mysql_result ($result,$i,"status");
$created_at=mysql_result ($result,$i,"created_at");
$scheduled_at=mysql_result ($result,$i,"scheduled_at");
$executed_at=mysql_result ($result,$i,"executed_at");
$finished_at=mysql_result ($result,$i,"finished_at");

//output html
echo "<tr>";
echo "<td>".$schedule_id."</td>";
echo '<td>'.$job_code."</td>"; 
echo '<td style="color: red;">'.$status."</td>"; 
echo "<td>".$created_at."</td>"; 
echo "<td>".$scheduled_at."</td>"; 
//echo "<td>".$executed_at."</td>"; 
//echo "<td>".$finished_at."</td>"; 
echo "</tr>";                 
$i++;
}
echo "</tbody></table><hr>";
//================================================================
//Succsessful jobs

mysql_connect($server,$user,$pass);
@mysql_select_db($db) or die("Unable to select database");
//$query="SELECT * FROM cron_schedule" ;
$query='SELECT * FROM `cron_schedule` WHERE `status` ="success" ORDER BY `executed_at` DESC' ;
$result=mysql_query($query);
$num=mysql_numrows($result);
echo "<h2>".$num." Jobs Succsessful</h2>";
echo '<table border="1"><tbody>';
echo "<tr><th>schedule_id</th><th>job_code</th><th>status</th><th>created_at</th><th>scheduled_at</th>";
echo "<th>executed_at</th><th>finished_at</th></tr>";
$i=0;
while ($i < $num) {

$schedule_id=mysql_result ($result,$i,"schedule_id");                                      
$job_code=  mysql_result($result,$i,"job_code");
$status=mysql_result ($result,$i,"status");
$created_at=mysql_result ($result,$i,"created_at");
$scheduled_at=mysql_result ($result,$i,"scheduled_at");
$executed_at=mysql_result ($result,$i,"executed_at");
$finished_at=mysql_result ($result,$i,"finished_at");

//output html
echo "<tr>";
echo "<td>".$schedule_id."</td>";
echo "<td>".$job_code."</td>"; 
echo "<td>".$status."</td>"; 
echo "<td>".$created_at."</td>"; 
echo "<td>".$scheduled_at."</td>"; 
echo "<td>".$executed_at."</td>"; 
echo "<td>".$finished_at."</td>"; 
echo "</tr>";                 
$i++;
}
echo "</tbody></table>";
//================================================================
?>
