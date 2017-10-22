<?php
/*
	mealplanner.php
	Jonah Backfish
	last modified: 10/18/17
	
	Contains MealPlanner class which provides methods for displaying the admin tools and
	interacting the with calendar/db.

	NOTES: 
		Tools still to implement: Export food csv list, clear all meals, add notes/events to mealpicker
		All JS still needs separated into it's own script
		A warning should appear when next/prev is clicked on the calendar
		Alphabetize food lists
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
	//Must set 'savedata' hidden input to the type of data being saved: "addNewFood", "deleteFood", "mealPlanChanged"
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
			$r=MealDB::runQuery("SELECT name FROM FoodItems");
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

<script>

var listOfChanges = []; //Keeps track of any changes made to the meal plans

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

//Return the first index of array that contains the specified value, returns -1 if nothing is found
function arrayContains(array, value){
	for (var x = 0; x<array.length; x++){
		if (array[x]==value) return x;
	}
	return -1;
}


//Code that adds events to the "Add to ..." buttons in the meal planner.
//Type: "Breakfast", "AM", "Luch", "PM", "Dinner"
function addBtn(type){
	var btn = document.getElementsByClassName("btnAdd"+type);
	if (btn){
		for (var i = 0; i < btn.length; i++) {
			btn[i].addEventListener("click", function( event ) {
				var parent = event.currentTarget.parentElement;
				var foodList = parent.getElementsByClassName('lstFoods')[0];
				var selFood = foodList.options[foodList.selectedIndex].value;
				if (selFood!="none"){
					var opt = document.createElement("option");
					var mealList = event.currentTarget.parentElement.getElementsByClassName('lst'+type+'List')[0];
					var mealListId = mealList.getAttribute('id');
					opt.text = selFood;
					opt.value = selFood;
					mealList.options.add(opt);
					//Add changes to the list of changes string, then update the hidden element that carries changes over through post
					if (arrayContains(listOfChanges,mealListId)==-1){
						var l = document.getElementById('changedMealLists');
						listOfChanges.push(mealListId);
						l.value = JSON.stringify(listOfChanges);
					}
				}
			}, false);
		}
	}
}
	
//Code that adds listeners for "Remove" buttons in the meal planner.
//Type: "Breakfast", "AM", "Luch", "PM", "Dinner"
function removeBtn(type){
	var btn = document.getElementsByClassName("btnRemove"+type);
	if (btn){
		for (var i = 0; i < btn.length; i++) {
			btn[i].addEventListener("click", function( event ) {
				var mealList = event.currentTarget.parentElement.getElementsByClassName('lst'+type+'List')[0];
				var mealListId = mealList.getAttribute('id');
				if (mealList.selectedIndex!=-1){//If something is selected
					mealList.removeChild(mealList[mealList.selectedIndex]);
					//Add changes to the list of changes string, then update the hidden element that carries changes over through post
					if (arrayContains(listOfChanges,mealListId)==-1){
						var l = document.getElementById('changedMealLists');
						listOfChanges.push(mealListId);
						l.value = JSON.stringify(listOfChanges);
					}
				}
			}, false);
		}
	}
}	
	
//Initializes the page and adds even listeners
function init() {
	
	//Since the browsers don't send listbox options in POST, we have to attach planned meals manually to a hidden element
	var btn = document.getElementById("btnMealSubmit");
	if (btn){
		btn.addEventListener("click", function( event ) {
			var l = {};
			for (var i =0; i<listOfChanges.length; i++){
				var clist = document.getElementById(listOfChanges[i]);
				l[listOfChanges[i]]=[];
				for (var z=0; z<clist.options.length; z++){
					l[listOfChanges[i]][z]=clist.options[z].value;
				}
			}
			document.getElementById("mpListOfFoods").value = JSON.stringify(l);
		}, false);
	}

	//Add events for the meal planner buttons (i.e., "Add food" and "Remove")
	addBtn("Breakfast");
	addBtn("AM");
	addBtn("Lunch");
	addBtn("PM");
	addBtn("Dinner");
	removeBtn("Breakfast");
	removeBtn("AM");
	removeBtn("Lunch");
	removeBtn("PM");
	removeBtn("Dinner");
}
window.addEventListener("load", init, false);
</script>