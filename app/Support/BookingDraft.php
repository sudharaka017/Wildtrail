<?php
namespace WildTrail\Support;

/**
 * Stores a tourist's unfinished booking in the PHP session while they inspect
 * a guide or vehicle profile. The draft is cleared after a successful booking.
 */
final class BookingDraft
{
    private const KEY = 'booking_draft';

    private const FIELDS = [
        'park_id',
        'date',
        'slot_id',
        'guests',
        'preferred_language',
        'vehicle_id',
        'guide_id',
        'pickup_location',
        'contact_phone',
        'notes',
    ];

    public function save(array $source): void
    {
        $draft = [];
        foreach (self::FIELDS as $field) {
            $draft[$field] = $source[$field] ?? '';
        }
        $_SESSION[self::KEY] = $draft;
    }

    public function get(): array
    {
        $draft = $_SESSION[self::KEY] ?? [];
        return is_array($draft) ? $draft : [];
    }

    public function clear(): void
    {
        unset($_SESSION[self::KEY]);
    }
}
