<?php
/*
	mealplanner.php
	Jonah Backfish
	last modified: 12/03/17
	
	Contains MealPlanner class which provides methods for displaying the admin tools and
	interacting the with calendar/db.
*/
require_once("settings.php");
require_once("db.php");
require_once("calendar.php");

class MealPlanner{
	
	private $calHandle;
	private $notifications;
	
	//Constructor used to initialize the meal planner. Accepts the name of the calendar to be used. If no calendar name is passed, the default
	//will be used or, if the cookie exists, will be loaded from the cookie.
	function __construct($calendarName=""){

		$this->notifications="";

		//Check for a cookie that indicates we are using a different calendar from the default or the one passed.
		if (isset($_COOKIE["useCalendar"]) && $calendarName==""){
			$calendarName=$_COOKIE["useCalendar"];
		}
		
		//If the user is changing to a different calendar, then we need to go ahead and select it before creating our calendar object
		if (isset($_POST['savedata']) && isset($_POST['submit'])){
			
			if ($_POST['submit']=='changeCalendar' && $_POST['savedata']=='calendarTools'){
				if ($_POST['selCalName']!="-1"){
					$calendarName=MealDB::getCalNameFromId($_POST['selCalName']);
					setcookie("useCalendar",$calendarName,0);
				}
			}
		}

		$this->calHandle = new Calendar($calendarName);
		$this->calHandle->set_callbacks("loadDate","nextPreviousCalendar");
		
		
		//Check if any data needs to be saved, and save it if so.
		if (isset($_POST['submit']) && isset($_POST['savedata'])){
			if ($this->saveData())		
				$this->addNotification("Changes successfully saved","lightgreen");
		}
	}
	
	//Adds a notification to the notification system
	//$notification=string of notification text
	//$color = color of notification background, i.e. "lightgreen" for success, "pink" for failure
	function addNotification($notification, $color="lightgreen"){
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

		if ($_POST['savedata']=='calendarTools'){
			if ($_POST['selCalName']=='-1' && $_POST['submit']!="addCalendar"){
				$this->addNotification("You must select a calendar first",'pink');
				return false;
			}
			switch($_POST['submit']){
				case "changeCalendar":
					//The logic for this operation had to be stored in the constructor, before the calendar was selected and loaded.
					$this->addNotification("The calendar has been selected. To use this calendar permenantly, set it as the default.");
					return true;
					break;

				case "defaultCalendar":
					$this->calHandle->setDefaultCalendar($_POST['selCalName']);
					$this->addNotification("The default calendar has been changed");
					return true;
					break;

				case "deleteCalendar":
					if ($this->calHandle->deleteCalendar($_POST['selCalName'])){ //Returns false if calendar is currently in use
						$this->addNotification("The calendar has been deleted");
						return true;
					}
					else{
						$this->addNotification("Cannot delete the calendar because it is in use. Select a different calendar first.",'pink');
						return false;
					}
					break;
				case "addCalendar":
					if (!isset($_POST['newCalName'])){
						$this->addNotification("Cannot add new calendar because a name has not been set.",'pink');
						return false;
					}
					if ($_POST['newCalName']==''){
						$this->addNotification("Cannot add a calendar with a blank name.",'pink');
						return false;
					}
					$name = $_POST['newCalName'];
					$r=MealDB::runQuery("SELECT * FROM Calendars WHERE name = '".$name."'");
					if (mysqli_num_rows($r)>0){
						$this->addNotification("Failed to create calendar. A calendar with the specified name already exists.",'pink');
						return false;
					}
					MealDB::runQuery("INSERT INTO Calendars (name) VALUES ('".$name."')");
					$this->addNotification("New calendar added. Select it to use it.");
					return true;
					break;
			}
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
						if (strpos($changedLists[$i],"List")){ //Adding a meal
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
						if (strpos($changedLists[$i],"otes")){ //Adding a note
							if (isset($_POST[$changedLists[$i]])){
								$note=$_POST[$changedLists[$i]];
								$date = substr($changedLists[$i],-10);
								//Remove the preexisting entry, and add a new one.
								MealDB::runQuery("DELETE FROM Notes WHERE date='".$date."' AND calendarId='".$this->calHandle->calId."'");
								if (trim($note)!=""){ //Don't add anything if it's empty
									MealDB::runQuery("INSERT INTO Notes (note,date,calendarId) VALUES('".addslashes($note)."','".$date."','".$this->calHandle->calId."')");
								}
							}
						}
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
		echo "\n\n<br><!--Delete Food Tools-->\n<div class='toolPanel' style='width:309px'>";

		echo "\n\t<form action='".basename($_SERVER['PHP_SELF'])."' method='post'>\n\t\t<fieldset><legend>Delete Food Items</legend>";
		echo "Delete items from the food database.<br>";
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

	//Shows a tool panel for adding new foods to the database.
	function showCalendarTools(){
		echo "\n\n<br><!--Calendar Tools-->\n<div class='toolPanel' style='width:612px;'>";
		echo "\n\t<form action='".basename($_SERVER['PHP_SELF'])."' method='post'>\n\t\t<fieldset><legend>Manage Calendars</legend>";
		echo "\n\t<input type='hidden' name='savedata' value='calendarTools'>";

		echo "\n\t\t<strong>Currently selected calendar: </strong>".$this->calHandle->calName."<br><br>";

		//Display all of the calendars in a listbox, label the default calendar
		echo "\n\t\t<select name='selCalName' id='selCalName'><option value='-1'>(select one)</option>";
		$r=MealDB::runQuery("SELECT name,isdefault,calendarId FROM Calendars  ORDER BY name");
		while ($g=mysqli_fetch_row($r)){
			$default = $g[1]=='1' ? " (default)" : "";
			echo "\n\t\t\t<option value='".$g[2]."'>".$g[0].$default."</option>";
		}
		echo "\n\t\t</select> <button type='submit' name='submit' value='changeCalendar'>Select This Calendar</button>";
		echo "\n\t\t<button type='submit' name='submit' value='defaultCalendar'>Set To Default</button>";
		echo "\n\t\t<button type='submit' name='submit' value='deleteCalendar' onclick='return confirmMessage(".'"Are you sure you want to permenantly delete the selected calendar? This action cannot be reversed."'.")'>Delete Calendar</button>";

		echo "\n\t\t<br><br><strong>Create New Calendar</strong><br><input type='text' name='newCalName' placeholder='Name of Calendar'/>  <button type='submit' name='submit' value='addCalendar'>Add New Calendar</button>";

		echo "\n\t</fieldset>\n\t</form>";
		echo "\n</div>";
	}
	
	//Shows the date picker
	function showDatePicker(){
		echo "\n\t<div class='center' style='width:165px'><strong>Select a date:</strong></div>";
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
			echo "\n\t\t<div style='display:inline-block'><strong>Date: </strong> ".$this->calHandle->selectedMonth."/".$i."/".$this->calHandle->selectedYear."</div>  <div class='floatRight'><strong>Calendar: </strong>".$this->calHandle->calName."</div><br><br>";
			
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

			echo "\n\t\t\t</div><br>";

			$notes = $this->calHandle->getNotes($this->calHandle->selectedMonth,$i,$this->calHandle->selectedYear);
			if (!$notes) $notes="";
			else $notes = stripslashes($notes);

			$mealRuleBox = "rules".$formattedDate;

			echo "<hr>\n\t\t\t<div style='display:inline-block'><label>Notes for this date: <br><textarea id='notes".$formattedDate."'  name='notes".$formattedDate."' class='toolsNotesEvents'>".$notes."</textarea></label></div>";

			echo "\n\t\t\t<div class='floatRight'><label>CACFP Recommendations - [<a href='standards_summary.pdf' target='_blank'>Detailed standards summary</a>]<br><div id='".$mealRuleBox."' class='toolsNotesEvents' style='background-image:url(img/standardsHover.png)'></div></label></div>";

			echo "<br></div>";
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
		$mealRuleBox = "rules".$formattedDate;
		$foodString = MealDB::getMeal($type,$formattedDate,$this->calHandle->calId);
		$id="lst".$type."List".$formattedDate;
		$strList = "\n\t\t\t<div class='mealList'><label>".$heading."<br><select style='width:140px' onmouseout='clearMealRules(".'"'.$mealRuleBox.'")'."' onmouseover='mealRules(".'"'.$type.'","'.$mealRuleBox.'")'."' size='10' class='lst".$type."List' id='".$id."'>";
		
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

	//Shows all administrator tools
	function showAdminPanel(){
		$this->showNotifications();
		echo "\n\t<div class='adminTools'>";

		echo "\n\t<div class='loadingDiv'><img src='img/loading.gif'></div>";

		echo "\n\t<div class='absolute' style='top:0px; left:5px;'>";
		$this->showCalendarTools();
		echo "\n\t</div>";

		echo "\n\t<div class='absolute' style='top:160px; left:5px;'>";
		$this->showAddFoodTools();
		echo "\n\t</div>";

		echo "\n\t<div class='absolute' id='mealPlannerPanel' style='top:160px; left:310px;'>";
		$this->showDeleteAllMealsTools();
		echo "\n\t</div>";

		echo "\n\t<div class='absolute' style='top:296px; left:310px;'>";
		$this->showDeleteFoodTools();
		echo "\n\t</div>";	

		echo "\n\t<div class='absolute' style='top:665px; left:15px;'>";
		$this->showDatePicker();
		echo "\n\t</div>";

		echo "\n\t<div class='absolute' style='top:550px; left:210px;'>";
		$this->showMealPicker();
		echo "\n\t</div>";
		echo "\n\t</fieldset>";

		echo "\n\t</div>";
	}
}
?>
