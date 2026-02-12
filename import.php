<?php

// this is for database
// un = username
$un="webuser";
$pw="CsUq*Be/1RnW[W.y";

// Connecting to a local database
$host="localhost";
$db="equipment";

// ODBC
$dblink=new mysqli($host,$un,$pw,$db); 

// load the data from the file for reading
// we know it's a csv, we will use a command to help process csv
$fp=fopen("equipment-part2.txt", "r");

$start=microtime(true);

// read until end of file
// $line = an array, fgetcsv which reads line and converts to array
while(($line=fgetcsv($fp)) !== FALSE)
{
	// line is an array index 0,1,2 account for each column
	$sql="Insert into `devices` 
	(`device_type`, `manufacturer_type`, `serial_number`) 
	values ('$line[0]', '$line[1]', '$line[2]')";
	$dblink->query($sql) or
		// error checking, check database is there
		die("Something went wrong with $sql<br>".$dblink->error);
}

$end=microtime(true);
fclose($fp);
$timeSeconds=$end-$start;
$timeMin=$timeSeconds/60;

//echo "<h2>Complete</h2>";
//echo "<h3>Time Seconds: $timeSeconds</h3>";
//echo "<h3>Time Minutes: $timeMin</h3>";

echo "Complete\n";
echo "Time Seconds: $timeSeconds\n";
echo "Time Minutes: $timeMin\n";
?>