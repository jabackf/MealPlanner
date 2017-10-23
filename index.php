
<html>

<?php

require_once("mealplanner.php");

$mp = new MealPlanner("calendar");
$mp->showNotifications();
$mp->showAddFoodTools();
$mp->showDeleteFoodTools();
$mp->showDatePicker();
$mp->showMealPicker();

/*
$cal = new Calendar("calendar");
echo "<h4>Calendar without callback:</h4>";
$cal->show_calendar();
*/
?>

<script src="js/mealplanner.js"></script>

</html>