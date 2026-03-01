<?php

/**
 * Example: Sync an Airbnb iCal feed and check availability
 * 
 * This demonstrates the typical workflow:
 * 1. Fetch iCal feed from external platform
 * 2. Parse booked dates
 * 3. Store in database
 * 4. Check availability for a requested period
 */

require_once __DIR__ . '/src/ICalParser.php';
require_once __DIR__ . '/src/CalendarStorage.php';

// --- Configuration ---
$dbHost = 'localhost';
$dbName = 'your_database';
$dbUser = 'your_user';
$dbPass = 'your_password';

$propertyId = 42;
$icalUrl = 'https://www.airbnb.com/calendar/ical/XXXXX.ics?s=XXXXX';

// --- Database connection ---
$pdo = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

// --- Fetch and parse iCal feed ---
$icalContent = file_get_contents($icalUrl);

if ($icalContent === false) {
    die("Error: Could not fetch iCal feed.\n");
}

$parser = new ICalParser();
$bookedPeriods = $parser->parse($icalContent);

echo "Found " . count($bookedPeriods) . " booked periods.\n";

// --- Store in database ---
$storage = new CalendarStorage($pdo);
$saved = $storage->saveBookedPeriods($propertyId, 'airbnb', $bookedPeriods);

echo "Saved {$saved} periods to database.\n";

// --- Check availability ---
$checkIn = '2026-04-10';
$checkOut = '2026-04-15';

$available = $storage->isPropertyAvailable($propertyId, $checkIn, $checkOut);

if ($available) {
    echo "Property #{$propertyId} is AVAILABLE for {$checkIn} to {$checkOut}.\n";
} else {
    echo "Property #{$propertyId} is NOT available for {$checkIn} to {$checkOut}.\n";
}

// --- Get available ranges for a month ---
$availableRanges = $parser->getAvailableRanges($bookedPeriods, '2026-04-01', '2026-04-30');

echo "\nAvailable periods in April 2026:\n";
foreach ($availableRanges as $range) {
    echo "  {$range['start']} to {$range['end']}\n";
}
