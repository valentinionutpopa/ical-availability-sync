<?php

/**
 * ICalParser - Parse iCal feeds from Booking.com, Airbnb and other platforms
 * 
 * Extracts booked date ranges from .ics calendar feeds and checks
 * property availability for given date ranges.
 * 
 * @author Valentin Ionut Popa
 * @license MIT
 */
class ICalParser
{
    /**
     * Parse an iCal string and extract booked date ranges
     *
     * @param string $icalContent Raw iCal content
     * @return array Array of booked periods ['start' => DateTime, 'end' => DateTime, 'summary' => string]
     */
    public function parse(string $icalContent): array
    {
        $events = [];
        $lines = explode("\n", str_replace("\r\n", "\n", $icalContent));

        $inEvent = false;
        $event = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === 'BEGIN:VEVENT') {
                $inEvent = true;
                $event = [];
                continue;
            }

            if ($line === 'END:VEVENT') {
                $inEvent = false;
                if (isset($event['DTSTART']) && isset($event['DTEND'])) {
                    $events[] = [
                        'start'   => $this->parseDate($event['DTSTART']),
                        'end'     => $this->parseDate($event['DTEND']),
                        'summary' => $event['SUMMARY'] ?? 'Booked',
                        'uid'     => $event['UID'] ?? null,
                    ];
                }
                continue;
            }

            if ($inEvent && strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                // Handle properties with parameters (e.g., DTSTART;VALUE=DATE:20240301)
                $key = explode(';', $key)[0];
                $event[$key] = $value;
            }
        }

        return $events;
    }

    /**
     * Check if a property is available for a given date range
     *
     * @param array  $bookedPeriods Array from parse()
     * @param string $checkIn       Check-in date (Y-m-d)
     * @param string $checkOut      Check-out date (Y-m-d)
     * @return bool True if available
     */
    public function isAvailable(array $bookedPeriods, string $checkIn, string $checkOut): bool
    {
        $requestStart = new \DateTime($checkIn);
        $requestEnd = new \DateTime($checkOut);

        foreach ($bookedPeriods as $period) {
            if ($requestStart < $period['end'] && $requestEnd > $period['start']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all available date ranges within a given period
     *
     * @param array  $bookedPeriods Array from parse()
     * @param string $from          Start of search range (Y-m-d)
     * @param string $to            End of search range (Y-m-d)
     * @return array Available periods ['start' => string, 'end' => string]
     */
    public function getAvailableRanges(array $bookedPeriods, string $from, string $to): array
    {
        $rangeStart = new \DateTime($from);
        $rangeEnd = new \DateTime($to);

        // Sort booked periods by start date
        usort($bookedPeriods, function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });

        // Filter to only periods within our range
        $relevant = array_filter($bookedPeriods, function ($period) use ($rangeStart, $rangeEnd) {
            return $period['start'] < $rangeEnd && $period['end'] > $rangeStart;
        });

        $available = [];
        $cursor = clone $rangeStart;

        foreach ($relevant as $period) {
            if ($cursor < $period['start']) {
                $available[] = [
                    'start' => $cursor->format('Y-m-d'),
                    'end'   => $period['start']->format('Y-m-d'),
                ];
            }
            if ($period['end'] > $cursor) {
                $cursor = clone $period['end'];
            }
        }

        if ($cursor < $rangeEnd) {
            $available[] = [
                'start' => $cursor->format('Y-m-d'),
                'end'   => $rangeEnd->format('Y-m-d'),
            ];
        }

        return $available;
    }

    /**
     * Parse iCal date string into DateTime object
     *
     * @param string $dateStr iCal date (YYYYMMDD or YYYYMMDDTHHiissZ)
     * @return \DateTime
     */
    private function parseDate(string $dateStr): \DateTime
    {
        $dateStr = trim($dateStr);

        if (strlen($dateStr) === 8) {
            return \DateTime::createFromFormat('Ymd', $dateStr)->setTime(0, 0, 0);
        }

        if (strpos($dateStr, 'T') !== false) {
            $dateStr = str_replace('Z', '', $dateStr);
            return \DateTime::createFromFormat('Ymd\THis', $dateStr);
        }

        return new \DateTime($dateStr);
    }
}
