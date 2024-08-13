<?php

// Define the year
$year = 2024;

// Create the main folder for the year
$yearFolder = __DIR__ . DIRECTORY_SEPARATOR . $year;

if (!file_exists($yearFolder)) {
    mkdir($yearFolder, 0777, true);
}

// Array of months and the number of days in each month
$months = [
    "01_January" => 31,
    "02_February" => ($year % 4 == 0 && $year % 100 != 0 || $year % 400 == 0) ? 29 : 28, // Leap year check for February
    "03_March" => 31,
    "04_April" => 30,
    "05_May" => 31,
    "06_June" => 30,
    "07_July" => 31,
    "08_August" => 31,
    "09_September" => 30,
    "10_October" => 31,
    "11_November" => 30,
    "12_December" => 31,
];

// Loop through each month
foreach ($months as $month => $days) {
    // Create the month folder
    $monthFolder = $yearFolder . DIRECTORY_SEPARATOR . $month;

    if (!file_exists($monthFolder)) {
        mkdir($monthFolder, 0777, true);
    }

    // Loop through each day of the month
    for ($day = 1; $day <= $days; $day++) {
        // Format the day with leading zero if necessary
        $dayFolder = $monthFolder . DIRECTORY_SEPARATOR . str_pad($day, 2, "0", STR_PAD_LEFT);

        if (!file_exists($dayFolder)) {
            mkdir($dayFolder, 0777, true);
        }
    }
}

echo "Folder structure created successfully.";

?>
