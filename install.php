<?php

sql('CREATE TABLE IF NOT EXISTS pinsets ( pinsets_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY , passwords LONGTEXT, description VARCHAR( 50 ) , addtocdr TINYINT( 1 ) , deptname VARCHAR( 50 ) , used_by VARCHAR( 255 ));') ;

/*
v1.0, original release
*/

$pinsets_thisVersion = '1.0';
$pinsets_installedVersion = modules_getversion($modulename);

/*
if (version_compare($pinsets_installedVersion, "1.1", "<")) {
	
}
*/

?>
