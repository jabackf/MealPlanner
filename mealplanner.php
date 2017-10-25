<?php
/*
	mealplanner.php
	Jonah Backfish
	last modified: 10/18/17
	
	Contains MealPlanner class which provides methods for displaying the admin tools and
	interacting the with calendar/db.

	NOTES: 
<<<<<<< HEAD
		Tools still to implement: Export food csv list, clear all meals, add notes/events to mealpicker, create/select calendar.
		
=======
		Tools still to implement: Export food csv list, clear all meals, add notes/events to mealpicker
>>>>>>> 8393a1cbd272f8b5ace29d75a5181007cd7d5a81
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
		$this->calHandle->set_callbacks("loadDate","nextPreviousCalendar");
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
	//Must set 'savedata' hidden input to the type of data being saved: "addNewFood", "deleteFood", "mealPlanChanged", "deleteMeals"
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

		if ($_POST['savedata']=='deleteMeals'){

			MealDB::runQuery("DELETE FROM MealItems WHERE CalendarId = '".$this->calHandle->calId."'");
			return true;

		}
		
		if ($_POST['savedata']=='mealPlanChanged'){
			if (isset($_POST['changedMealLists'])){
				if  ($_POST['changedMealLists']==""){
					$this->addNotification("No changes have been detected. Nothing has been saved.","pink");
					return false;
				}
				else{

					$changedLists = json_decode($_POST['changedMealLists'],true);
					$allFoods = json_decode($_POST['mpListOfFoods'],true);
					
					for ($i=0; $i < count($changedLists); $i++){
						$foodString="";
						$cFoods = $allFoods{$changedLists[$i]};
						$addPipe=false;
						for ($z=0; $z<count($cFoods); $z++){
							$foodId = mysqli_fetch_row(MealDB::runQuery("SELECT foodId FROM FoodItems WHERE name='".$cFoods[$z]."'"))[0];
							if ($addPipe){ 
								$foodString.="|";
							}
							$foodString .= $foodId;
							$addPipe = true;
						}

						$date = substr($changedLists[$i],-10);
						$type = substr($changedLists[$i],3,-14);
						
						//Add the new meal
						MealDB::addMeal($type, $foodString,$date,$this->calHandle->calId);
					}
					return true;
				}
			}
			else {
				$this->addNotification("ERROR: Change list not found. Nothing has been saved.","pink");
				return false;
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
		$r=MealDB::runQuery("SELECT name FROM FoodItems  ORDER BY name");
		while ($g=mysqli_fetch_row($r)){
			echo "\n\t\t\t<option value='".$g[0]."'>".$g[0]."</option>";
		}
		echo "\n\t\t</select>";
		echo "\n\t\t <input type=submit name='submit' value='Delete Food Item'>";
		echo "\n\t</fieldset>\n\t</form>";
		echo "\n</div>";
	}
	//Shows a tool panel for deleted foods from the database.
	function showDeleteAllMealsTools(){
		echo "\n\n<br><!--Delete All Meals Tools-->\n<div class='toolPanel'>";
		echo "\n\t<form action='".basename($_SERVER['PHP_SELF'])."' method='post'>\n\t\t<fieldset><legend>Delete All Meals</legend>";
		echo "\n\t<input type='hidden' name='savedata' value='deleteMeals'>";
		echo "\n\t\t<strong>Warning:</strong> This option will delete all meals<br/>for the currently selected calendar.<br><br>";
		echo "\n\t\t <input type='submit' onclick='return confirmMessage(".'"WARNING! This will permenantly remove all planned meals stored in the database for the current calendar. Are you sure you want to continue?"'.")' name='submit' class='center' value='Delete All Meals'>";
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
		echo "\n\t\t<form action='".basename($_SERVER['PHP_SELF'])."' method='post'>\n\t\t<fieldset><legend>Create ".$this->calHandle->GetMonthString($this->calHandle->selectedMonth)." Meal Plan</legend>";
		echo "\n\t\t<input type='hidden' name='savedata' value='mealPlanChanged'>";
		echo "\n\t\t<input type='hidden' name='changedMealLists' id='changedMealLists'>";//A hidden input that stores the names of all modified meal list boxes as JSON
		echo "\n\t\t<input type='hidden' name='mpListOfFoods' id='mpListOfFoods'>";//A hidden input that stores the names of all modified meal list boxes as JSON

		
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
			echo "\n\t\t<strong>Date: </strong> ".$this->calHandle->selectedMonth."/".$i."/".$this->calHandle->selectedYear."<br>";
			
			echo "\n\t\t\t<strong>Select Foods: </strong> <select class='lstFoods'><option value='none'>(select one)</option>";
			$r=MealDB::runQuery("SELECT name FROM FoodItems ORDER BY name");
			while ($g=mysqli_fetch_row($r)){
				echo "\n\t\t\t\t<option value='".$g[0]."'>".$g[0]."</option>";
			}
			echo "\n\t\t\t</select>";
			echo " <button type=button class='btnAddBreakfast'>Add To Breakfast</button> ";
			echo "<button type=button class='btnAddAM'>Add To AM Snack</button> ";
			echo "<button type=button class='btnAddLunch'>Add To Lunch</button> ";
			echo "<button type=button class='btnAddPM'>Add To PM Snack</button> ";
			echo "<button type=button class='btnAddDinner'>Add To Dinner</button><br/><br/><hr>";
			
			$formattedDate = $this->calHandle->selectedYear."-".str_pad($this->calHandle->selectedMonth, 2, "0", STR_PAD_LEFT)."-".str_pad($i, 2, "0", STR_PAD_LEFT);
			
			echo "\n\t\t\t<div class='mealListParent'>";
			$this->addMealList("Breakfast","Breakfast",$formattedDate);
			$this->addMealList("AM","AM Snack",$formattedDate);
			$this->addMealList("Lunch","Lunch",$formattedDate);
			$this->addMealList("PM","PM Snack",$formattedDate);
			$this->addMealList("Dinner","Dinner",$formattedDate);

			echo "\n\t\t\t</div><br></div>";
		}
		
		echo "\n\t\t<br><br><input type='submit' name='submit' id='btnMealSubmit' value='Save All Meal Changes'>";
		echo "\n\t\t</fieldset>\n\t</form>";	
		echo "\n\t</div>";	
		echo "\n</div>";
	}

	//Displays a meal list of type. Used by ShowMealPlannerTools
	//$type = "Breakfast", "AM", "Lunch", "PM", "Dinner"
	//Heading is a string title that shows above the listbox
	//formattedDate is a string formatted as a SQL date "YYYY-MM-DD"
	function addMealList($type,$heading,$formattedDate){

		$foodString = MealDB::getMeal($type,$formattedDate,$this->calHandle->calId);
		$strList = "\n\t\t\t<div class='mealList'><label>".$heading."<br><select style='width:140px' size='10' class='lst".$type."List' id='lst".$type."List".$formattedDate."'>";
		
		if ($foodString!=false) { //If there is an existing meal for this date...
			$foods = explode("|",$foodString);
			for ($z=0; $z<count($foods); $z++){
				$strList.="\n\t\t\t\t<option>".$foods[$z]."</option>";
			}
		}

		$strList.= "\n\t\t\t</select></label>";
		$strList.= "\n\t\t\t<br/><button type=button class='btnRemove".$type."'>Remove</button></div>";
		echo $strList;
	}
}
?>
