
<html>
<h3>Meal Planner Test Page</h3>
<a href="https://sdev265meal.000webhostapp.com/">https://sdev265meal.000webhostapp.com/</a><br>
<a href="localhost/meal">localhost/meal</a><br><br>
<body>
<?php

require_once("mealplanner.php");

$mp = new MealPlanner();
$mp->showAdminPanel();


/*
$cal = new Calendar("calendar");
echo "<h4>Calendar without callback:</h4>";
$cal->show_calendar();
*/
?>

<script src="js/mealplanner.js"></script>
</body>
</html>