<?php
function getWeekDates($timestamp, $includeSat = false) {
    $date = new DateTime();
    $date->setTimestamp($timestamp);

    // Find the start (Sunday) and end (Saturday) of the week
    $date->modify('this week -1 day');
    $startOfWeek = $date->format('Y-m-d');
    $date->modify('+5 days');
    if ($includeSat) {
        $date->modify('+1 day'); // Include Saturday
    }
    $endOfWeek = $date->format('Y-m-d');

    // Create an array of dates for each day of the week
    $weekDates = [];
    $currentDate = new DateTime($startOfWeek);

    while ($currentDate <= new DateTime($endOfWeek)) {
        $weekDates[] = $currentDate->format('Y-m-d');
        $currentDate->modify('+1 day');
    }

    return $weekDates;
}

// Example usage:
$timestamp = strtotime('2023-08-09'); // Replace with your timestamp
$includeSaturday = true; // Set to true to include Saturday

$weekDates = getWeekDates($timestamp, $includeSaturday);

foreach ($weekDates as $date) {
    echo $date . "\n";
}