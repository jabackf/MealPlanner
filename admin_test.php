
<html>
<body>

<h3>Meal Planner - Admin Tool Test</h3>
<a href="index.php">End User Calendar Test</a><br/>
<!--<a href="https://sdev265meal.000webhostapp.com/">https://sdev265meal.000webhostapp.com/</a><br>-->

<?php

require_once("mealplanner.php");

$mp = new MealPlanner();
$mp->showAdminPanel();


?>

<script src="js/mealplanner.js"></script>
</body>
</html>