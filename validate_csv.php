<?php

// check from terminal 

// check command line argument

// $argc = number of arguments passed in
// expect at least 2: script name + CSV file

if($argc < 2) {
    echo "Usage: php validate_csv.php file.csv\n";
    exit(1); // Stop script
}

// $argv[1] is the first real argument (the CSV file path) 
$file = $argv[1];

// make sure the file exists
if(!is_file($file)) {
    echo "File not found.\n";
    exit(1);
}

// error log file
// create a log file named based on the CSV file
$logFile = __DIR__ . "/errors_" . basename($file) . ".log";

// clear old log content so each run starts fresh
file_put_contents($logFile,"");




/* Regex
    - Hex: /^SN-[0-9a-fA-F]+$/
    - Alphanumeric: /^SN-[0-9A-ZA-Z]+$/
    - Any non empty: /^SN-.+$/

*/
$idRegex = '/^SN-[0-9a-fA-f]+$/';



// Function to log errors
function log_error($logFile, $lineNumber, $code, $message, $rawLine) {
    // Remove newline character from raw line for clean log
    $rawLine = trim ($rawLine);

    // Format the log message
    $entry = "[line $lineNumber] [$code] $message | raw=$rawLine\n";

    // Append the error message to the log file
    file_put_contents($logFile, $entry, FILE_APPEND);
}


// Build sets for lookup
$typeSet = [];
$manufacturerSet = [];

$scanHandle = fopen($file, "r");
while(($scanLine = fgets($scanHandle)) !== false) {
    if(trim($scanLine) === "") continue;

    $scanRow = str_getcsv($scanLine, ",", '"', "\\");

    // Field count must be at least 2 to read type and manufacturer
    if(count($scanRow) >= 2) {
        $t = trim((string)($scanRow[0] ?? ""));
        $m = trim((string)($scanRow[1] ?? ""));

        if($t !== "") $typeSet[$t] = true;
        if($m !== "") $manufacturerSet[$m] = true;
    }
}
fclose($scanHandle);




// Open CSV file
// fopen() opens file for reading
$handle = fopen($file, "r");

// track which line we are on
$lineNumber = 0;

// count how many errors we find
$errorCount = 0;

// Count for total lines of errors
$totalLines = 0;

// Track duplicates
$seenSerials = [];

// Read file line by line
while(($rawLine = fgets($handle)) !== false) {

    // Increase line number counter
    $lineNumber++;

    // skip completely empty lines
    if(trim($rawLine) === "") continue;

    // Count non-empty lines
    $totalLines++;

    // convert the csv line into an array of fields
    $row = str_getcsv($rawLine, ",", '"', "\\");

    // count how many columns were found
    $count = count($row);

    // Field count validation 
    // must have exactly 3 fields
    if($count < 3) {
        log_error($logFile, $lineNumber, "MISSING_FIELD", "Expected 3 fields, got $count", $rawLine);
        $errorCount++;
        continue; // move to next line
    }

    if($count > 3) {
        log_error($logFile, $lineNumber, "TOO_MANY_FIELDS", "Expected 3 fields, got $count", $rawLine);
        $errorCount++;
    }

    // If we get here we have exactly 3 fields
    //[$type, $brand, $serial] = $row;
    $type = trim((string)($row[0] ?? ""));
    $brand = trim((string)($row[1] ?? ""));
    $serial = trim((string)($row[2] ?? ""));

    // Empty field check
    if(trim($type) === "" || trim($brand) === "" || trim($serial) === "" ) {
        log_error($logFile, $lineNumber, "EMPTY_FIELD", "One or more fields are empty", $rawLine);
        $errorCount++;
        continue; 
    }

    // INVALID character check
    if(strpos($type, "'") !== false ||
       strpos($brand, "'") !== false ||
       strpos($serial, "'") !== false) {

       log_error($logFile, $lineNumber, "INVALID_CHAR", "Apostrophe found", $rawLine);
       $errorCount++;
       }

    if($type !== "" && !isset($typeSet[$type])) {
        log_error($logFile, $lineNumber, "BAD_TYPE", "Device type not recognized", $rawLine);
        $errorCount++;
    }

    if($brand !== "" && !isset($manufacturerSet[$brand])) {
        log_error($logFile, $lineNumber, "BAD_MANUFACTURER", "Manufacturer not recognized", $rawLine);
        $errorCount++;
    }

    //Identifier regex check
    if($serial !== "" && !preg_match($idRegex, $serial)) {
        log_error($logFile, $lineNumber, "BAD_IDENTIFIER", "Identifier does not match regex", $rawLine);
        $errorCount++;
    }

    // Duplicate identifier check
    if($serial !== "") {
        if(isset($seenSerials[$serial])) {
            $firstLine = $seenSerials[$serial];
            log_error($logFile, $lineNumber, "DUPLICATE_IDENTIFIER", "Identifier already seen on $firstLine", $rawLine);
            $errorCount++;
        }
        else {
            $seenSerials[$serial] = $lineNumber;
        }
        }

    

       
}

// Clean up and summary
// Close file
fclose($handle);
// Show result in terminal
echo "Validation complete.\n";
echo "Errors found: $errorCount out of $totalLines lines\n";
echo "Log file: $logFile\n";

?>