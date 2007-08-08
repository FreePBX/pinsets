<?php

global $db;
global $amp_conf;

$autoincrement = (($amp_conf["AMPDBENGINE"] == "sqlite") || ($amp_conf["AMPDBENGINE"] == "sqlite3")) ? "AUTOINCREMENT":"AUTO_INCREMENT";

$sql = "CREATE TABLE IF NOT EXISTS pinsets ( 
	pinsets_id INTEGER NOT NULL  PRIMARY KEY $autoincrement, 
	passwords LONGTEXT, 
	description VARCHAR( 50 ) , 
	addtocdr TINYINT( 1 ) , 
	deptname VARCHAR( 50 ) , 
	used_by VARCHAR( 255 )
)";

$check = $db->query($sql);
if(DB::IsError($check)) {
        die_freepbx("Can not create `pinsets` table\n");
}

?>
