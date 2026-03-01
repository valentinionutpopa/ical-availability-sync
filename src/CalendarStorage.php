<?php

/**
 * CalendarStorage - Store and retrieve synced calendar data using PDO
 * 
 * Uses prepared statements for all database operations.
 * Compatible with MySQL 5.7+ and MariaDB 10.2+
 * 
 * @author Valentin Ionut Popa
 * @license MIT
 */
class CalendarStorage
{
    private \PDO $db;

    /**
     * @param \PDO $db PDO connection instance
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Save booked periods for a property
     *
     * @param int    $propertyId Property ID
     * @param string $source     Calendar source (e.g., 'booking', 'airbnb', 'direct')
     * @param array  $periods    Booked periods from ICalParser::parse()
     * @return int Number of periods saved
     */
    public function saveBookedPeriods(int $propertyId, string $source, array $periods): int
    {
        // Remove old entries for this property + source before inserting new ones
        $stmt = $this->db->prepare(
            "DELETE FROM booked_periods WHERE property_id = :property_id AND source = :source"
        );
        $stmt->execute([
            ':property_id' => $propertyId,
            ':source'      => $source,
        ]);

        $stmt = $this->db->prepare(
            "INSERT INTO booked_periods (property_id, source, date_start, date_end, summary, external_uid, synced_at)
             VALUES (:property_id, :source, :date_start, :date_end, :summary, :external_uid, NOW())"
        );

        $count = 0;
        foreach ($periods as $period) {
            $stmt->execute([
                ':property_id'  => $propertyId,
                ':source'       => $source,
                ':date_start'   => $period['start']->format('Y-m-d'),
                ':date_end'     => $period['end']->format('Y-m-d'),
                ':summary'      => $period['summary'],
                ':external_uid' => $period['uid'],
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Get all booked periods for a property within a date range
     *
     * @param int    $propertyId Property ID
     * @param string $from       Start date (Y-m-d)
     * @param string $to         End date (Y-m-d)
     * @return array
     */
    public function getBookedPeriods(int $propertyId, string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            "SELECT date_start, date_end, source, summary 
             FROM booked_periods 
             WHERE property_id = :property_id 
               AND date_start < :to 
               AND date_end > :from 
             ORDER BY date_start ASC"
        );
        $stmt->execute([
            ':property_id' => $propertyId,
            ':from'        => $from,
            ':to'          => $to,
        ]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Check if a property is available for given dates (across all sources)
     *
     * @param int    $propertyId Property ID
     * @param string $checkIn    Check-in date (Y-m-d)
     * @param string $checkOut   Check-out date (Y-m-d)
     * @return bool
     */
    public function isPropertyAvailable(int $propertyId, string $checkIn, string $checkOut): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) 
             FROM booked_periods 
             WHERE property_id = :property_id 
               AND date_start < :check_out 
               AND date_end > :check_in"
        );
        $stmt->execute([
            ':property_id' => $propertyId,
            ':check_in'    => $checkIn,
            ':check_out'   => $checkOut,
        ]);

        return (int)$stmt->fetchColumn() === 0;
    }

    /**
     * Get last sync timestamp for a property + source
     *
     * @param int    $propertyId Property ID
     * @param string $source     Calendar source
     * @return string|null Last sync datetime or null
     */
    public function getLastSyncTime(int $propertyId, string $source): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT MAX(synced_at) 
             FROM booked_periods 
             WHERE property_id = :property_id AND source = :source"
        );
        $stmt->execute([
            ':property_id' => $propertyId,
            ':source'      => $source,
        ]);

        return $stmt->fetchColumn() ?: null;
    }
}
