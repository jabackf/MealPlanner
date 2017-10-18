<?php

/*
	db.php
	Jonah Backfish
	last modified: 10/16/17
	
	Contains static MealDB class and all functions related to database interaction
	Class containing basic database settings is in settings.php
*/

require_once("settings.php");

class MealDB{

	private static $config; //Object that holds DB settings. Assigned in connect function.
	private static $dbHandle = null;  //Handle used to internally reference DB. Assigned in connect method
	private static $dbName; //Name of the database
	private static $logfileHandle = null; //A handle used for the query log file. See settings.php
	
	//Connects to the database
	static function connect(){
		self::$config = new Settings();
		self::$dbName = self::$config->dbName;
		
		if (self::$config->dump_queries){
			self::$logfileHandle=fopen(self::$config->querylog_fname,'a')
			or die("Unable to open ".self::$config->querylog_fname." for appending");
		}
		
		self::$dbHandle = mysqli_connect(self::$config->host,self::$config->uname,self::$config->pass)
		or
		die("Connection to SQL server could not be established.\n");
		
		//Check if the database exists. Call create_new_database if not.
		if (!self::db_exists(self::$dbName)){
			self::create_new_database();
		}
		
		//Check if the database is empty, and populate it if so
		if(mysqli_num_rows(self::runQuery("SHOW TABLES FROM ".self::$dbName)) == 0){
			self::populateNewDatabase();
		}

		//Use the database
		$result = mysqli_select_db(self::$dbHandle,self::$dbName)
		or
		die("<br/>".self::$dbName." database could not be selected.".mysql_error());

	} //end function connect
	
	//Returns rather or not the database with the given name exists
	static function db_exists($name){
		$exists=false;
		$result = self::runQuery("SHOW DATABASES");      
		while ($row = mysqli_fetch_array($result)) {       
			if ( $row[0]==$name)
				$exists=true;
		}
		return $exists;
	}
	
	//Creates a new database with tables and fields if one doesn't exist
	//Note: This could fail if the privelidges are not setup correctly. In that case, import the empty DB into MySQL
	static function create_new_database(){
	
		//Create DB
		self::runQuery("CREATE DATABASE ".self::$dbName); 
		
		//Use the database
		$result = mysqli_select_db(self::$dbHandle,self::$dbName)
		or
		die("<br/>".self::$dbName." database could not be selected.".mysql_error());
		self::populateNewDatabase();
	}
	
	//Adds the basic table structure to an empty database, the populates it with food items from the default foods csv file.
	static function populateNewDatabase(){
	
		//Create FoodGroup Table
		$sql = "CREATE TABLE FoodGroups (
				groupId INT(6) UNSIGNED AUTO_INCREMENT, 
				name VARCHAR(30) NOT NULL,
				PRIMARY KEY (groupId))";
		self::runQuery($sql); 
		
		//Create FoodItem Table
		$sql = "CREATE TABLE FoodItems (
				foodId INT(6) UNSIGNED AUTO_INCREMENT, 
				name VARCHAR(90) NOT NULL,
				PRIMARY KEY (foodId))";
		self::runQuery($sql); 		
		
		//Create Food Table
		$sql = "CREATE TABLE Foods (
				foodId INT(6) UNSIGNED,
				groupId INT(6) UNSIGNED,
				FOREIGN KEY (foodId) REFERENCES FoodItems(foodId),
				FOREIGN KEY (groupId) REFERENCES FoodGroups(groupId))";
		self::runQuery($sql);
		
		//Create MealType Table
		$sql = "CREATE TABLE MealTypes (
				mealTypeId INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
				type VARCHAR(20) NOT NULL)";
		self::runQuery($sql); 
		
		//Create Calendar Table
		$sql = "CREATE TABLE Calendars (
				calendarId INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
				name VARCHAR(20) NOT NULL)";
		self::runQuery($sql); 
				
		//Create MealItem Table
		$sql = "CREATE TABLE MealItems (
				id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
				mealTypeId INT(6) UNSIGNED,
				foodId INT(6) UNSIGNED,
				date DATE,
				calendarId INT(6) UNSIGNED,
				 FOREIGN KEY (mealTypeId) REFERENCES MealTypes(mealTypeId),
				 FOREIGN KEY (foodId) REFERENCES FoodItems(foodId),
				 FOREIGN KEY (calendarId) REFERENCES Calendars(calendarId))";
		self::runQuery($sql); 	

		//Create User Table
		$sql = "CREATE TABLE Users (
				user VARCHAR(60) NOT NULL PRIMARY KEY,
				passwordHash CHAR(40) NOT NULL)";
		self::runQuery($sql); 
		
		//Load default foods into database
		self::loadCSVFoods();
	} //End populate new database
	
	//Adds a new food item to the database if it doesn't already exist.
	//$item = name of food item.
	//$groups = list of associated food groups delineated with a pipe "|", e.g. "Grains|Fruits and Vegetables"
	static function addFoodItem($item,$groups){
		$r=self::runQuery("SELECT name FROM FoodItems WHERE name='".$item."'");
		if ($r->num_rows==0){ //If the food item doesn't exist in the DB yet...
			self::runQuery("INSERT INTO FoodItems (name) VALUES ('".$item."')"); //...insert it into the DB...
			
				foreach (explode("|",$groups) as $g){ //...then insert food groups...
				$r=self::runQuery("SELECT name FROM FoodGroups WHERE name='".$g."'");
				if ($r->num_rows==0){ //..if they don't already exist, that is...
					self::runQuery("INSERT INTO FoodGroups (name) VALUES ('".$g."')");
				}
				//Then add the link between the foodItem and the foodGroup to the Foods table
				$foodId = mysqli_fetch_row(self::runQuery("SELECT foodId FROM FoodItems WHERE name='".$item."'"))[0];
				$groupId = mysqli_fetch_row(self::runQuery("SELECT groupId FROM FoodGroups WHERE name='".$g."'"))[0];
				self::runQuery("INSERT INTO Foods (foodId, groupId) VALUES ('".$foodId."','".$groupId."')");
			}
		}
	}
	
	//Looks for default foods (and associated groups) as CSV file (fname specified in settings.php), then loads the data into the database.
	static function loadCSVFoods(){
		$fn=self::$config->default_foods_csv;
		if (file_exists($fn)){
			$data = file_get_contents($fn);
			$data=explode(",",$data);
			
			for($i=0; $i<count($data); $i+=2){
				self::addFoodItem($data[$i],$data[$i+1]);
			}
		}
	}
	
	//Exports foods stored in database as a CSV file specified by $fname, overwriting it if it exists
	static function exportCSVFoods($fname){
	
		//Delete file if it exists
		if (file_exists($fname)) unlink($fname);
	
		$fh=fopen($fname,'w')
		or die("Unable to open ".$fname." for writing");
		
		$first_run=true;
		
		$result=self::runQuery("SELECT * FROM FoodItems");
		while($item=mysqli_fetch_row($result)){
			$id=$item[0];
			$foodName=$item[1];
			if (!$first_run){
				fwrite($fh,",");
			}
			else{
				$first_run=false;
			}
			fwrite($fh,$foodName);
			$r=self::runQuery("SELECT groupId FROM Foods WHERE foodId = '".$id."'");
			$add_pipe=false;
			while($foodrow=mysqli_fetch_row($r)){
				$groupId=$foodrow[0];
				$g=mysqli_fetch_array(self::runQuery("SELECT name FROM FoodGroups WHERE groupId = '".$groupId."'"))[0];
				if ($add_pipe) fwrite($fh,"|");
				else fwrite($fh,",");
				fwrite($fh,$g);
				if ($add_pipe==false) $add_pipe=true;
			}
		}
	}//End export CSV function
	
	//Sanitizes database input.
	static function scrub($input){
		return stripcslashes(trim(/*remove tabs*/preg_replace('/\t+/', '',mysqli_real_escape_string(self::$dbHandle,$input))));
	}
	
	//Sanitizes and runs SQL query. Logs it if the dump_queries debugging option is set to true.
	static function runQuery($q){
		$q=self::scrub($q);
		if (self::$config->dump_queries){
			fwrite(self::$logfileHandle,PHP_EOL.PHP_EOL."Timestamp: ".date("Y-m-d H:i:s",time()).PHP_EOL."Attempting following query:".PHP_EOL.$q);
		}
		$r = mysqli_query(self::$dbHandle,$q)  or die("Error executing query: ".mysql_error());
		if (self::$config->dump_queries){
			fwrite(self::$logfileHandle,PHP_EOL."Query succesfully executed.");
		}
		return $r;
	}
	
	//Returns true if connected to db, false if not
	static function is_connected(){
		if (self::$dbHandle==false || self::$dbHandle==null){
			return false;
		}
		else{
			return true;
		}
	}
	
	//Closes connected, releases log file handle, etc.
	static function close(){
		mysqli_close(self::$dbHandle);
		if (self::$logfileHandle){
			fclose(self::$logfileHandle);
		}
	}
}

MealDB::connect();