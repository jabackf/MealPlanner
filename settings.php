<?php
/*
	settings.php
	Jonah Backfish
	last modified 10/16/17
	
	Contains general settings and debug parameters for the system
*/

class Settings{
	
	//Changes various settings/functionality based on where the system is running (e.g., locally, server1, server2, etc.)
	public $deployment_mode="000webhost"; //"local", "000webhost"
	
	//When set to true, the system will log all mysql queries executed through MealDB::runQuery to $querylog_fname
	//Entries are timestamped and appended to the end of the file if it already exists.
	public $dump_queries=false;
	public $querylog_fname="db/querylog.txt";
	
	//DB Settings
	public $dbName, $host, $uname, $pass;
	public $default_foods_csv="db/default_foods.csv";//CSV file stores default foods
	
	//Assigns settings based on deployment mode. e.g. "local", "server", ect.
	function __construct(){
		if ($this->deployment_mode=="local"){
			$this->dbName = "meal";
			$this->host = "localhost";
			$this->uname = "root";
			$this->pass = "";
		}
		if ($this->deployment_mode=="000webhost"){
			$this->dbName = "id2889622_meal";
			$this->host = "localhost";
			$this->uname = "id2889622_jbackfish";
			$this->pass = 'ivq*$ITXPG';
		}
	}
}

?>