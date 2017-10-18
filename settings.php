<?php
/*
	settings.php
	Jonah Backfish
	last modified 10/16/17
	
	Contains general settings for the meal planner and database
*/

class Settings{
	
	//Changes various settings/functionality based on where the system is running (e.g., locally, server1, server2, etc.)
	public $deployment_mode="local"; //"local", "000webhost"
	
	//DB Settings
	public $name, $host, $uname, $pass;
	public $default_foods_csv="db/default_foods.csv";//CSV file stores default foods
	
	//Assigns settings based on deployment mode. e.g. "local", "server", ect.
	function __construct(){

		$this->name = "meal";
		
		if ($this->deployment_mode=="local"){
			$this->host = "localhost";
			$this->uname = "root";
			$this->pass = "";
		}
		if ($this->deployment_mode=="000webhost"){
			$this->host = "localhost";
			$this->uname = "user";
			$this->pass = "pass";
		}
	}
}

?>