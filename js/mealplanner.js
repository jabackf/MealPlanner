
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

//Javascript that hides/shows calendar data panels
function calMouseOverDate() {
	var dates = document.getElementsByClassName("calendar_date");
	
	//Add mouseover event to display panel and move to mouse coordinates
	for (var i = 0; i < dates.length; i++) {
		dates[i].addEventListener("mouseenter", function( event ) {
			var panel = document.getElementById("date_data"+event.target.id)
			if (panel !=null){ 
				panel.style['display'] = "block";
				var xOffset=Math.max(document.documentElement.scrollLeft,document.body.scrollLeft);
				var yOffset=Math.max(document.documentElement.scrollTop,document.body.scrollTop);
				panel.style.left = event.clientX+xOffset+10;
				panel.style.top = event.clientY+yOffset+10;
				event.target.style["background-image"]="url('img/fuzzyball.png')";
				event.target.style["background-position"]="-8px -3px";
			}

		}, false);
	}
	
	//Add mouseout event to hide panel
	for (var i = 0; i < dates.length; i++) {
		dates[i].addEventListener("mouseleave", function( event ) {
			var panel = document.getElementById("date_data"+event.target.id)
			if (panel !=null){ 
				panel.style['display'] = "none";
				event.target.style["background-color"]="transparent";
				event.target.style["background-image"]="none";
			}

		}, false);
	}
}

//Called when next/previous links are clicked on calendar
//np contains "next" or "previous"
//url is a string containing the url that loads the next month
function nextPreviousCalendar(np, url){
	if (listOfChanges.length>0){
		if (window.confirm('You have unsaved meal changes for this month. Any unsaved changes will be lost if you switch to the '+np+' month. Would you like to continue anyway?'))
		{
			window.location.href = url;
		}
	}
	else{
		window.location.href = url;
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

	//Add the events that show / hide calendar data panels
	calMouseOverDate();
}

window.addEventListener("load", init, false);
