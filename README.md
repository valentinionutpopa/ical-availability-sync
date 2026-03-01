# iCal Availability Sync

A lightweight PHP library for syncing availability calendars from platforms like **Booking.com** and **Airbnb** using the iCal (.ics) format.

Built for accommodation booking platforms that need to aggregate availability from multiple sources and prevent double bookings.

## Features

- Parse iCal (.ics) feeds from any platform (Booking.com, Airbnb, Google Calendar, etc.)
- Extract booked date ranges with source tracking
- Check property availability for specific date ranges
- Find all available periods within a given timeframe
- Store synced data in MySQL using PDO prepared statements
- Handles both date-only and datetime iCal formats

## Requirements

- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.2+
- PDO extension

## Installation

1. Clone the repository:
```bash
git clone https://github.com/valentinionutpopa/ical-availability-sync.git
```

2. Create the database table:
```bash
mysql -u your_user -p your_database < schema.sql
```

3. Include the classes in your project:
```php
require_once 'src/ICalParser.php';
require_once 'src/CalendarStorage.php';
```

## Quick Start

### Parse an iCal feed
```php
$parser = new ICalParser();
$icalContent = file_get_contents('https://www.airbnb.com/calendar/ical/XXXXX.ics');
$bookedPeriods = $parser->parse($icalContent);
```

### Check availability
```php
$available = $parser->isAvailable($bookedPeriods, '2026-04-10', '2026-04-15');
```

### Store in database
```php
$storage = new CalendarStorage($pdo);
$storage->saveBookedPeriods($propertyId, 'airbnb', $bookedPeriods);
```

### Query availability from database
```php
$available = $storage->isPropertyAvailable(42, '2026-04-10', '2026-04-15');
```

See [example.php](example.php) for a complete workflow.

## Database Schema

The `booked_periods` table stores synced calendar data with indexes optimized for availability queries:

| Column | Type | Description |
|--------|------|-------------|
| property_id | INT | Property identifier |
| source | VARCHAR(50) | Platform source (booking, airbnb, direct) |
| date_start | DATE | Booking start date |
| date_end | DATE | Booking end date |
| summary | VARCHAR(255) | Event description from iCal |
| external_uid | VARCHAR(255) | Unique ID from iCal event |
| synced_at | DATETIME | Last sync timestamp |

## License

MIT
