This is a landing page for the SDEV 265 meal planner project.<br><br>

<?php

require_once("calendar.php");
require_once("db.php");

//show_calendar("dis",0,"callback_test");
$calendar = new Calendar("calendar");
$calendar->set_callback("callback_test");
$calendar->show_calendar(0);


?>
<script>

function callback_test(m,d,y) {
	window.alert(m+"/"+d+"/"+y);
	
}

</script>