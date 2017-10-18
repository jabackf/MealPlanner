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
	private static $dbName = "meal";
	//Connects to the database
	static function connect(){
		self::$config = new Settings();
		
		$connection = mysql_connect(self::$config->host,self::$config->uname,self::$config->pass)
		or
		die("Connection to SQL server could not be established.\n");
		
		//Check if the database exists. Call create_new_database if not.
		if (!self::db_exists(self::$dbName)){
			self::create_new_database();
		}

		//Use the database
		$result = mysql_select_db(self::$dbName)
		or
		die("<br/>".self::$dbName." database could not be selected.".mysql_error());
		
		self::$dbHandle = $connection;
	} //end function connect2db
	
	//Returns rather or not the database with the given name exists
	static function db_exists($name){
		$exists=false;
		$result = mysql_query("SHOW DATABASES")  or die(mysql_error());      
		while ($row = mysql_fetch_array($result)) {       
			if ( $row[0]==$name)
				$exists=true;
		}
		return $exists;
	}
	
	//Creates a new database with tables and fields if one doesn't exist
	//Note: This could fail if the privelidges are not setup correctly. In that case, import the empty DB into MySQL
	static function create_new_database(){
	
		//Create DB
		mysql_query("CREATE DATABASE ".self::$dbName)  or die("Could not create database: ".mysql_error()); 
		
		//Use the database
		$result = mysql_select_db(self::$dbName)
		or
		die("<br/>".self::$dbName." database could not be selected.".mysql_error());
		
		//Create FoodGroup Table
		$sql = "CREATE TABLE FoodGroups (
				groupId INT(6) UNSIGNED AUTO_INCREMENT, 
				name VARCHAR(30) NOT NULL,
				PRIMARY KEY (groupId))";
		mysql_query($sql)  or die("Error Creating DB: ".mysql_error()); 
		
		//Create FoodItem Table
		$sql = "CREATE TABLE FoodItems (
				foodId INT(6) UNSIGNED AUTO_INCREMENT, 
				name VARCHAR(90) NOT NULL,
				PRIMARY KEY (foodId))";
		mysql_query($sql)  or die("Error Creating DB: ".mysql_error()); 		
		
		//Create Food Table
		$sql = "CREATE TABLE Foods (
				foodId INT(6) UNSIGNED,
				groupId INT(6) UNSIGNED,
				FOREIGN KEY (foodId) REFERENCES FoodItems(foodId),
				FOREIGN KEY (groupId) REFERENCES FoodGroups(groupId))";
		mysql_query($sql)  or die("Error Creating DB: ".mysql_error());
		
		//Create MealType Table
		$sql = "CREATE TABLE MealTypes (
				mealTypeId INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
				type VARCHAR(20) NOT NULL)";
		mysql_query($sql)  or die("Error Creating DB: ".mysql_error()); 
		
		//Create Calendar Table
		$sql = "CREATE TABLE Calendars (
				calendarId INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
				name VARCHAR(20) NOT NULL)";
		mysql_query($sql)  or die("Error Creating DB: ".mysql_error()); 
				
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
		mysql_query($sql)  or die("Error Creating DB: ".mysql_error()); 	

		//Create User Table
		$sql = "CREATE TABLE Users (
				user VARCHAR(60) NOT NULL PRIMARY KEY,
				passwordHash CHAR(40) NOT NULL)";
		mysql_query($sql)  or die("Error Creating DB: ".mysql_error()); 
		
		//Load default foods into database
		self::loadCSVFoods();
	}
	
	//Looks for default foods data CSV (specified in settings.php), then loads data into the database.
	static function loadCSVFoods(){
		$fn=self::$config->default_foods_csv;
		if (file_exists($fn)){
			$data = file_get_contents($fn);
			$data=explode(",",$data);
			
			for($i=0; $i<count($data); $i+=2){
				//echo "\nFood: ".$data[$i];
				//echo "\nGroup(s): ";
				foreach (explode("|",$data[$i+1]) as $g){
					//echo  $g.", ";
				}
			}
		}
	}
	
	//Exports foods as a CSV file specified by $fname
	static function exportCSVFoods($fname){
	
	}
	
	//Sanitizes database input.
	static function scrub($input){
		return(stripslashes(mysql_real_escape_string($input)));
	}
	
	//Sanitizes and runs a SQL query
	static function runQuery($q){
		mysql_query(self::scrub($q))  or die("Error executing query: ".mysql_error());
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
}

MealDB::connect();

MealDB::loadCSVFoods();