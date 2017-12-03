
<html>
<body>
<h3>Meal Planner - Admin Tools Test</h3>
<div style="width:500px">
The tools below allow the administrator to create meal plans and manage calendars.<br/><br/>
<a href="index.php">End User Calendar Test</a><br/><br/>
</div>

<!--<a href="https://sdev265meal.000webhostapp.com/">https://sdev265meal.000webhostapp.com/</a><br>-->

<?php

require_once("mealplanner.php");

$mp = new MealPlanner();
$mp->showAdminPanel();


?>

<script src="js/mealplanner.js"></script>
</body>
</html>