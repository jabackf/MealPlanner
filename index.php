
<html>
<body>
<h3>Meal Planner - End User Calendar Test</h3>
<div style="width:500px">
Welcome to the meal planner test application! This is my final submission for SDEV265. The meal planner system was tested primarily on Google Chrome, and cross browser compatibility has not yet been fully implemented. The interactive calendar below is designed to be embedded on the end-user web pages. To test the administrative tools, use the link below.<br/><br/>
<a href="admin_test.php">Administrative Tools Test Page</a><br/><br/>
</div>
<!--<a href="https://sdev265meal.000webhostapp.com/">https://sdev265meal.000webhostapp.com/</a><br>-->

<?php

require_once("mealplanner.php");

$cal = new Calendar("calendar");
$cal->show_calendar();

?>

<script src="js/mealplanner.js"></script>
</body>
</html>