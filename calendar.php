<link href="css/meal.css" rel="stylesheet" type="text/css">

<?php

/*
	calendar.php
	Jonah Backfish
	last modified: 10/16/17
	
	Contains calendar class for the meal planner. This class contains all methods
	for generating and displaying interactive and printable calendars, picking
	dates, etc.

	NOTES:
	Finish mealDataExists and the data panel creator to account for events
	Added $this->nextPrevCallback and variable set in mealPlanner. Next I need to implement it in the anchors for next/previous
*/

class Calendar{

	private $callback = "";  //Stores an optional JS callback function that is called when a date is clicked. month, day, and year are passed
	private $nextPreviousCallback=""; //Stores an optional JS callback function for when the next or previous date is clicked. "next" or "previous" are passed
	private $calName = "default"; //The name of the calendar, used to reference the database
	public $calId; //The id used to reference the calendar in the database
	public $selectedMonth;
	public $selectedYear;
	
	//$calName - The name of the calendar. If the calendar does not exist in the database, a new one will be created.
	function __construct($calName){
		if ($calName){
			$this->calName = $calName;
		}
		
		//Find the ID in the database, and create a new calendar if it doesn't already exist
		$r=MealDB::runQuery("SELECT calendarId FROM Calendars WHERE name = '".$this->calName."'");
		if ($r->num_rows==0){ //A new calendar
			MealDB::runQuery("INSERT INTO Calendars (name) VALUES ('".$this->calName."')");
			$r=MealDB::runQuery("SELECT calendarId FROM Calendars WHERE name = '".$this->calName."'");
			$this->calId = mysqli_fetch_array($r)[0];
		}
		else{
			$this->calId = mysqli_fetch_array($r)[0];
		}
		
		$this->selectedYear= date('Y');
		$this->selectedMonth= date('m');

		//Check the URL for d and y variables. Otherwise, use the current month and year.
		if (!empty($_GET['m']))
		{
			$this->selectedMonth=$_GET['m'];
		}
		if (!empty($_GET['y']))
		{
			$this->selectedYear=$_GET['y'];
		}
		
	}
	
	//Set the javascript callback function
	function set_callbacks($date_callback, $nextprev_callback=""){
		$this->callback=$date_callback;
		$this->nextPrevCallback=$nextprev_callback;
	}

	//Return the number of days in a given month
	function days_in_month($month, $year)
	{
		return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
	}

	//Returns 1 for monday, through to 7 for sunday
	function day_of_the_week($m, $d, $y)
	{
		$h = mktime(0, 0, 0, $m, $d, $y);
		return date("N", $h) ;
	}

	//Returns the name of the week day (monday, tuesday, etc)
	function day_of_the_week_name($m, $d, $y)
	{
		$h = mktime(0, 0, 0, $m, $d, $y);
		return date("l", $h) ;
	}

	//Returns the name of the months (January, February, etc.)
	function GetMonthString($n)
	{
		$timestamp = mktime(0, 0, 0, $n, 1, 2005);

		return date("F", $timestamp);
	}

	//Displays a link to the current version of the printable calendar
	function show_printable_calendar_link()
	{
		echo "Function not yet implemented";
	}

	//Shows a small interactive calendar for date picking and viewing meal data
	//arguments:
	//to be called when a date is clicked. The callback is passed three arguments: month, day, and year.
	function show_calendar()
	{
		echo "<!--- BEGIN CALENDAR -->\n";

		$width=165;  //horizontal space between calendar numbers
		$height=203;  //vertical space between calendar numbers
		$hoffset=0;

		echo "<div name='calendar' class='calendar'>";

		
		$m=$this->selectedMonth;
		$y=$this->selectedYear;

		//Get the next and previous month/year
		$m=str_pad($m, 2, "0", STR_PAD_LEFT);
		$nm=$m+1;
		$ny=$y;
		if ($nm>=13)
		{
			$nm=1;
			$ny=$ny+1;
		}
		$pm=$m-1;
		$py=$y;
		if ($pm<=0)
		{
			$pm=12;
			$py=$py-1;
			if ($py<=0)
			{
				$py=1;
			}
		}

		//Remove variables from URL and use it to create next and previous links as $nurl and $purl
		$url = explode("?", $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
		$url=$url[0];

		if (strpos($url,"?",0))
		{
			$nurl=$url."&m=".$nm."&y=".$ny;
			$purl=$url."&m=".$pm."&y=".$py;
		}
		else
		{
			$nurl=$url."?m=".$nm."&y=".$ny;
			$purl=$url."?m=".$pm."&y=".$py;
		}

		//Start creating the table
		echo "\n\t\t<table class='calendar_table' width='".$width."' height='".$height."' background='img/calendar.gif' >";
		echo "\n\t\t\t<th colspan=7 class='calendar_header'><b>".$this->GetMonthString($m)." ".$y."</th >\n\t\t<tr height=10>\n\t\t\t<td></td>\n\t\t\t</tr><tr>";

		$first = $this->day_of_the_week($m, 1, $y);
		if ($first==7)
		{
			$first=0;
		}
		$c=$first;

		for ($i=1; $i<=$first; $i+=1)
		{
			echo "\n\t\t<td></td>";
		}
		for ($i=1; $i<=$this->days_in_month($m, $y); $i+=1)
		{
			$col='black';
			$dataExists = $this->mealDataExists($m,$i,$y);

			if ($dataExists)
			{
				$col="blue";
			}
			if ($m==date('m') && $y==date('Y') && $i==date('d'))
			{
				$col="red";
			}
			if ($c>6)
			{
				$c=0;
				echo "\n\t\t\t</tr><tr>";
			}
			
			//Show the date on the calendar
			echo "\n\t\t\t\t<td style='color:".$col."' class='calendar_date' id='".$i."'>";
			if ($this->callback){
				echo "<a style='color:".$col."' href='javascript:".$this->callback."(".$m.",".$i.",",$y.");'>";
			}
			echo $i;
			if ($this->callback){
				echo "</a>";
			}
			echo "</td>";
			$c=($c+1);
		}

		if (($this->days_in_month($m, $y)+$first)<=28)
		{
			echo "\n\t\t\t</tr><tr height=24><td></td></tr>";
		}
		if (($this->days_in_month($m, $y)+$first)<=35)
		{
			echo "\n\t\t\t</tr><tr height=24><td></td></tr>";
		}
		echo "\n\t\t</table>";

		echo "\n\t<div class='center'><a href='http://".$purl."#calendar'>prev</a> - "."<a href='http://".$nurl."#calendar'>next</a></div>\n</div><br>\n";


		//Create data panels that show/hide on mouse over
		echo "\t<!---Add panels that show data for each date on mouse over-->\n\t<div>";
		for ($i=1; $i<=$this->days_in_month($m, $y); $i+=1)
		{
			$dataExists = $this->mealDataExists($m,$i,$y);
			if ($dataExists){
				$mealTypes=['Breakfast','AM','Lunch','PM','Dinner'];
				$mealHeaders=['Breakfast','AM Snack','Lunch','PM Snack','Dinner'];
				echo "\n\t\t<div class='date_data' id='date_data".$i."'><strong>Date: ".$m."/".$i."/".$y."</strong><br/>";
				for ($z=0; $z<count($mealTypes); $z+=1){
					$formattedDate = $y."-".str_pad($m, 2, "0", STR_PAD_LEFT)."-".str_pad($i, 2, "0", STR_PAD_LEFT);
					$foodString = MealDB::getMeal($mealTypes[$z],$formattedDate,$this->calId);
					if ($foodString!=false){
						echo "\n\t\t\t<strong>".$mealHeaders[$z]."</strong><br><ul>";
						$foods = explode("|",$foodString);

						for ($f = 0; $f<count($foods); $f+=1){
							echo "\n\t\t\t<li>".$foods[$f]."</li>";
						}
						echo "\n\t\t\t</ul>";
					}
				}
				echo"\n\t\t</div>";
			}
		}

		echo "</div>\n\t<!--- END CALENDAR -->\n";
	}

	//Returns true if any meal or event data exists for the given date
	function mealDataExists($m,$d,$y){
		$formattedDate = $y."-".str_pad($m, 2, "0", STR_PAD_LEFT)."-".str_pad($d, 2, "0", STR_PAD_LEFT);
		$r=MealDB::runQuery("SELECT * FROM MealItems WHERE date = '".$formattedDate."'");
		if (mysqli_num_rows($r)>0) return true;
		//Check for event data
		return false;
	}
}//End Calendar class
?>

