<?php

/*
	mealplanner.php
	Jonah Backfish
	last modified: 10/18/17
	
	Contains MealPlanner class which provides methods for displaying the admin tools and
	interacting the with calendar/db.
*/

require_once("settings.php");
require_once("db.php");
require_once("calendar.php");

class MealPlanner{
	
	private $calName;
	private $calHandle;
	private $notifications;
	
	//Constructor used to initialize the meal planner. Accepts the name of the calendar to be used.
	function __construct($calendarName="default"){
		$this->calName=$calendarName;
		$this->calHandle = new Calendar($calendarName);
		$this->calHandle->set_callback("loadDate");
		$this->notifications="";
		
		//Check if any data needs to be saved, and save it if so.
		if (isset($_POST['submit']) && isset($_POST['savedata'])){
			if ($this->saveData())		
				$this->addNotification("Changes successfully saved","lightgreen");
		}
	}
	
	//Adds a notification to the notification system
	//$notification=string of notification text
	//$color = color of notification background, i.e. "lightgreen" for success, "pink" for failure
	function addNotification($notification, $color){
		$this->notifications.="\n<div class='notification' style='background-color:".$color."'>".$notification."</div>";
	}
	
	//Displays the notification
	function showNotifications(){
		echo "\n<div class='notifications'>".$this->notifications."</div>";
	}
	
	//Saves any data collected through the POST method to the database. Returns true on success.
	//Must set 'savedata' hidden input to the type of data being saved: "addNewFood", "deleteFood",
	function saveData(){
		if ($_POST['savedata']=='addNewFood'){
			if (!isset($_POST['groupNames']) || $_POST['foodName']==""){
				$this->addNotification("You must enter a food name and select at least ONE food group","pink");
				return false;
			}
			else{
				$groupString="";
				$groups=$_POST['groupNames'];
				$add_pipe=false;
				foreach ($groups as $group){
					if ($add_pipe) $groupString.="|";
					$groupString.=$group;
					$add_pipe=true;
				}
				MealDB::addFoodItem($_POST['foodName'],$groupString);
				return true;
			}
		}
		
		if ($_POST['savedata']=='deleteFood'){
			if ($_POST['foodName']=="none"){
				$this->addNotification("You haven't selected a food to delete","pink");
				return false;
			}
			else{
				$id = mysqli_fetch_row(MealDB::runQuery("SELECT foodId FROM foodItems WHERE name='".$_POST['foodName']."'"))[0];
				MealDB::runQuery("DELETE FROM Foods WHERE foodID = '".$id."'");
				MealDB::runQuery("DELETE FROM FoodItems WHERE foodID = '".$id."'");
				return true;
			}
		}
	}
	
	//Shows a tool panel for adding new foods to the database.
	function showAddFoodTools(){
		echo "\n\n<br><!--Add New Food Tools-->\n<div class='toolPanel'>";
		echo "\n\t<form action='".basename($_SERVER['PHP_SELF'])."' method='post'>\n\t\t<fieldset><legend>Add New Food Items</legend>";
		echo "\n\t<input type='hidden' name='savedata' value='addNewFood'>";
		echo "\n\t\t\t<label>Food Name: <input name='foodName'></label>";
		$r=MealDB::runQuery("SELECT name FROM FoodGroups");
		echo "\n\t\t\t<br><br><label>Belongs to the following food groups: <br><div class='indentForty'>";
		while ($g=mysqli_fetch_row($r)){
			echo "\n\t\t\t\t<input type='checkbox' name='groupNames[]' value='".$g[0]."'>".$g[0]."</input><br>";
		}
		echo "\n\t\t\t</div></label>";
		echo "\n\t\t<br><input type=submit name='submit' value='Save'>";
		echo "\n\t</fieldset>\n\t</form>";
		echo "\n</div>";
	}
	
	//Shows a tool panel for deleted foods from the database.
	function showDeleteFoodTools(){
		echo "\n\n<br><!--Delete Food Tools-->\n<div class='toolPanel'>";
		echo "\n\t<form action='".basename($_SERVER['PHP_SELF'])."' method='post'>\n\t\t<fieldset><legend>Delete Food Items</legend>";
		echo "\n\t<input type='hidden' name='savedata' value='deleteFood'>";
		echo "\n\t\t<select name='foodName'><option value='none'>(select one)</option>";
		$r=MealDB::runQuery("SELECT name FROM FoodItems");
		while ($g=mysqli_fetch_row($r)){
			echo "\n\t\t\t<option value='".$g[0]."'>".$g[0]."</option>";
		}
		echo "\n\t\t</select>";
		echo "\n\t\t <input type=submit name='submit' value='Delete Food Item'>";
		echo "\n\t</fieldset>\n\t</form>";
		echo "\n</div>";
	}
	
	//Shows the date picker
	function showDatePicker(){
		$this->calHandle->show_calendar();
	}
	
	//Shows the meal planning tool
	function showMealPicker(){
		echo "\n\n<br><!--Meal Picker Tools-->\n<div class='toolPanel'>";
		
		$numDays=$this->calHandle->days_in_month($this->calHandle->selectedMonth, $this->calHandle->selectedYear);
		if (date('m')==$this->calHandle->selectedMonth && date('Y')==$this->calHandle->selectedYear){
			$selectedDay=date('d');
		}
		else {
			$selectedDay=1;
		}
		
		for ($i=1; $i<=$numDays; $i+=1){
		
			$showHideString = ($i==$selectedDay) ? "style='display:inline-block'" : "style='display:none'";
		
			echo "\n\t<div class='mealPlannerPanel' ".$showHideString." id='mpdate-".$i."'>";
			echo "\n\t\t<form action='".basename($_SERVER['PHP_SELF'])."' method='post'>\n\t\t<fieldset><legend>Create Meal Plan</legend>";
			echo "\n\t\t<input type='hidden' name='savedata' value='addMeal'>";
			
			echo "\n\t\t<h3>Date: ".$this->calHandle->selectedMonth."/".$i."/".$this->calHandle->selectedYear."<br>";
			
			echo "\n\t\t\t<select class='lstFoods'><option value='none'>(select one)</option>";
			$r=MealDB::runQuery("SELECT name FROM FoodItems");
			while ($g=mysqli_fetch_row($r)){
				echo "\n\t\t\t\t<option value='".$g[0]."'>".$g[0]."</option>";
			}
			echo "\n\t\t\t</select>";
			echo " <button type=button class='btnAddBreakfast'>Add To Breakfast</button> ";
			echo "<button type=button class='btnAddAM'>Add To AM Snack</button> ";
			echo "<button type=button class='btnAddLunch'>Add To Lunch</button> ";
			echo "<button type=button class='btnAddPM'>Add To PM Snack</button> ";
			echo "<button type=button class='btnAddDinner'>Add To Dinner</button><br/><br/>";
			
			$formattedDate = $this->calHandle->selectedYear."-".str_pad($this->calHandle->selectedMonth, 2, "0", STR_PAD_LEFT)."-".str_pad($i, 2, "0", STR_PAD_LEFT);
			$r=MealDB::runQuery("SELECT 'id', 'mealTypeId', 'foodId' FROM MealItems WHERE date = '".$formattedDate."'");
			
			$strBreakfastList = "<label>Breakfast<br><select size='10' class='lstBreakfastList'>";
			
			$strBreakfastList.= "<option>Option1</option><option>Option2</option></select></label>";
			
			echo $strBreakfastList;
			
			echo "\n\t\t\t";
			
			echo "\n\t\t</fieldset>\n\t</form>";	
			echo "\n\t</div>";
		}
		
		//Have one save button for all panels. Use hidden fields to detect changes
		
		echo "\n</div>";
	}
}

?>

<script>

function hideAllMealPanels(){
	panels = document.getElementsByClassName("mealPlannerPanel");
	for (var i = 0; i < panels.length; i++) {
		panels[i].style['display'] = "none";
	}
}

function loadDate(m,d,y) {
	hideAllMealPanels();
	panel=document.getElementById("mpdate-"+d);
	if (panel !=null){ 
		panel.style['display'] = "inline-block";
	}
}

	
//Initializes the page and adds even listeners
function init() {
	
	var btn = document.getElementsByClassName("btnAddBreakfast");
	if (btn){
		for (var i = 0; i < btn.length; i++) {
			btn[i].addEventListener("click", function( event ) {
				var foodList = event.currentTarget.parentElement.getElementsByClassName('lstFoods')[0];
				var selFood = foodList.options[foodList.selectedIndex].value;
				var opt = document.createElement("option");
				var mealList = event.currentTarget.parentElement.getElementsByClassName('lstBreakfastList')[0];
				//mealList.options.add(opt);
				opt.text = selFood;
				opt.value = selFood;
				mealList.options.add(opt);
				
			}, false);
		}
	}
}

window.addEventListener("load", init, false);

</script>