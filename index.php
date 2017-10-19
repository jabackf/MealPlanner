
<?php

require_once("mealplanner.php");
require_once("db.php");

$mp = new MealPlanner("calendar");
$mp->showNotifications();
$mp->showAddFoodTools();
$mp->showDeleteFoodTools();
$mp->showDatePicker();
$mp->showMealPicker();
?>
