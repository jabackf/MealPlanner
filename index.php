
<html>
<body>
<h3>Meal Planner - End User Calendar Test</h3>
<a href="admin_test.php">Administrative Tools Test Page</a><br/>
<!--<a href="https://sdev265meal.000webhostapp.com/">https://sdev265meal.000webhostapp.com/</a><br>-->

<?php

require_once("mealplanner.php");

$cal = new Calendar("calendar");
echo "<h4>Calendar without callback:</h4>";
$cal->show_calendar();

?>

<script src="js/mealplanner.js"></script>
</body>
</html>