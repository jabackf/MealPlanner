<?php
require_once("db.php");
function day_of_the_week_name($m, $d, $y)
{
	$h = mktime(0, 0, 0, $m, $d, $y);
	return date("l", $h) ;
}
?>

<html>
<link href="css/print.css" rel="stylesheet" type="text/css">
<body>

	<img src='img/logo.gif' class = 'floatLeft'>
	<div class='headerText'>
	<h2>Meal Calendar</h2>

<?php
$month=$_GET['m'];
$timestamp = mktime(0, 0, 0, $month, 1, 2005);
$monthName = date("F", $timestamp);
$year = $_GET['y'];
$start = $_GET['s'];
$end = $_GET['e'];
$calId = $_GET['id'];
$length = $end-$start;

$mealTypes=['Breakfast','AM','Lunch','PM','Dinner'];
$mealHeaders=['Breakfast','AM Snack','Lunch','PM Snack','Dinner'];
$mealDataFound = array(false,false,false,false,false);
$notesFound=false;
$notes = array();
$dateDataFound = array();
$data = array();

echo "\n\t<h3>".$monthName." ".$year.",   [Dates ".$start." - ".$end."]</h3></div><br><br><br>";

echo "<table>";

for ($i=0; $i<$length; $i++){
	$formattedDate = $year."-".str_pad($month, 2, "0", STR_PAD_LEFT)."-".str_pad($start+$i, 2, "0", STR_PAD_LEFT);
	$dateDataFound[$i]=false;
	$date[$i]=array();
	for ($m = 0; $m<count($mealTypes); $m++){
		$foods=MealDB::getMeal($mealTypes[$m], $formattedDate, $calId);

		if ($foods){
			$dateDataFound[$i]=true;
			$mealDataFound[$m]=true;
			$data[$i][$m]=$foods;
		}
		else {
			$data[$i][$m]=false;
		}
	}

	$note = MealDB::getNotes($month,$i+$start,$year,$calId);
	if ($note){
		$notesFound = true;
		$dateDataFound[$i]=true;
		$notes[$i]=$note;
	}
	else{
		$notes[$i]=false;
	}
}

//Print the header, an empty cell followed by a cell for each used meal type.
echo "<tr><td></td>";
for ($m = 0; $m<count($mealTypes); $m++){
	if ($mealDataFound[$m]){
		echo "<td><strong>".$mealHeaders[$m]."</strong></td>";
	}
}
if ($notesFound) echo "<td><strong>Notes</strong></td>";
echo "</tr>";

for ($i=0; $i<$length; $i++){
	if($dateDataFound[$i]){ //If data exists for this date
		$day=$i+$start;
		echo "<tr><td><strong>".day_of_the_week_name($month,$day,$year)."</strong><br>".$day."/".$month."</td>";

		for ($m = 0; $m<count($mealTypes); $m++){
			if ($mealDataFound[$m]){
				if ($data[$i][$m]){
					echo "<td>".str_replace("|","<br/>",$data[$i][$m])."</td>";
				}
				else{
					echo "<td></td>";
				}
			}
		}
		if ($notesFound){
			echo "<td>";
			if ($notes[$i]) echo $notes[$i];
			echo "</td>";
		}
		echo "</tr>";
	}
}

echo "</table>";


?>

</body>