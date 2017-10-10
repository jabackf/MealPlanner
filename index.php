This is a landing page for the SDEV 265 meal planner project.<br><br>

<?php

include("calendar.php");

show_calendar("dis",0,"callback_test");
?>
<script>

function callback_test(m,d,y) {
	window.alert(m+"/"+d+"/"+y);
	
}

</script>