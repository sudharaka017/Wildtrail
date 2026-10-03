<?php
namespace WildTrail\Services;
use PDO;
use WildTrail\Exceptions\BookingException;
use WildTrail\Repositories\GuideRepository;
use WildTrail\Repositories\VehicleRepository;
final class BookingService {
    public function __construct(private PDO $pdo, private VehicleRepository $vehicles, private GuideRepository $guides) {}
    public static function slotKey(array $slot): string {
        $explicit=(string)($slot['slot_key'] ?? '');
        if(in_array($explicit,['dawn','morning','afternoon'],true)) return $explicit;
        $text=strtolower((string)$slot['label']);
        if(str_contains($text,'dawn')) return 'dawn';
        if(str_contains($text,'afternoon')) return 'afternoon';
        return 'morning';
    }
    public function createSafari(array $data, int $visitorId): int {
        $this->pdo->beginTransaction();
        try {
            $slot=$this->lockSlot((int)$data['park_id'], (int)$data['slot_id'], (string)$data['date']);
            if(!$slot || (int)$slot['used'] >= (int)$slot['vehicle_cap']) throw new BookingException('That safari time is already full. Please choose another date or time.');
            if((int)$slot['daily_used'] >= (int)$slot['daily_vehicle_cap']) throw new BookingException('This park has reached its daily safari vehicle limit. Please choose another date.');
            $slotKey=self::slotKey($slot);
            $vehicle=$this->vehicles->lockAvailable((int)$data['vehicle_id'], (int)$data['guests'], (int)$data['park_id']);
            if(!$vehicle) throw new BookingException('The selected vehicle is no longer available or is not approved for this park. Please choose another available jeep.');
            if($this->vehicles->isDriverClosed((int)$vehicle['driver_id'],$data['date'],$slotKey)) throw new BookingException('The selected vehicle/driver is unavailable for this safari time.');
            if($this->vehicles->hasConflict((int)$data['vehicle_id'],$data['date'],$slotKey)) throw new BookingException('That vehicle was just booked. Please choose another available jeep.');
            $guide=$this->guides->lockAvailable((int)$data['guide_id'], (int)$data['park_id']);
            if(!$guide) throw new BookingException('The selected guide is no longer available or is not approved for this park. Please choose another guide.');
            if($data['preferred_language']!=='' && stripos((string)$guide['languages'],$data['preferred_language'])===false) throw new BookingException('The selected guide does not offer your preferred language.');
            if($this->guides->isClosed((int)$data['guide_id'],$data['date'],$slotKey)) throw new BookingException('The selected guide is unavailable for this safari time.');
            if($this->guides->hasConflict((int)$data['guide_id'],$data['date'],$slotKey)) throw new BookingException('That guide was just booked. Please choose another available guide.');
            $entry=(float)$slot['entry_fee']*(int)$data['guests'];
            $vehicleFee=(float)$vehicle['base_price'];
            $guideFee=(float)$guide['guide_fee'];
            $amount=$entry+$vehicleFee+$guideFee;
            $code='WT'.date('ymd').strtoupper(bin2hex(random_bytes(3)));
            // Guide/vehicle are pre-verified by staff. A valid availability check is therefore enough
            // to approve the reservation automatically; payment is the final confirmation step.
            $st=$this->pdo->prepare("INSERT INTO bookings(booking_code,visitor_id,park_id,slot_id,entry_date,guests,driver_id,vehicle_id,guide_id,status,driver_response,payment_status,approval_status,approved_at,amount,entry_fee_component,vehicle_fee_component,guide_fee_component,preferred_language,pickup_location,contact_phone,special_notes,reserved_until) VALUES(?,?,?,?,?,?,?,?,?,'pending','accepted','unpaid','approved',NOW(),?,?,?,?,?,?,?,?,DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
            $st->execute([$code,$visitorId,$data['park_id'],$data['slot_id'],$data['date'],$data['guests'],$vehicle['driver_id'],$data['vehicle_id'],$data['guide_id'],$amount,$entry,$vehicleFee,$guideFee,$data['preferred_language'],$data['pickup_location'],$data['contact_phone'],$data['notes']]);
            $bookingId=(int)$this->pdo->lastInsertId();
            $n=$this->pdo->prepare('INSERT INTO notifications(user_id,message) VALUES(?,?)');
            $n->execute([$visitorId,"Safari $code reserved. Your verified guide and jeep are available. Complete payment within 15 minutes to confirm your trip."]);
            $n->execute([(int)$data['guide_id'],"New safari $code reserved for {$data['date']} ({$slot['label']})."]);
            $n->execute([(int)$vehicle['driver_id'],"New safari $code reserved for {$data['date']} ({$slot['label']})."]);
            $this->pdo->commit();
            return $bookingId;
        } catch (\Throwable $e) {
            if($this->pdo->inTransaction()) $this->pdo->rollBack();
            if($e instanceof BookingException) throw $e;
            throw new BookingException('The booking could not be completed. Please try again.',0,$e);
        }
    }
    private function lockSlot(int $parkId, int $slotId, string $date): ?array {
        // Lock the park first so bookings in different safari times cannot race past the daily cap.
        $p=$this->pdo->prepare("SELECT entry_fee,daily_vehicle_cap FROM parks WHERE id=? AND status='active' FOR UPDATE");
        $p->execute([$parkId]);
        $park=$p->fetch();
        if(!$park) return null;
        $q=$this->pdo->prepare("SELECT vehicle_cap,label,slot_key FROM park_slots WHERE id=? AND park_id=? AND is_active=1 FOR UPDATE");
        $q->execute([$slotId,$parkId]);
        $slot=$q->fetch();
        if(!$slot) return null;
        $slot['entry_fee']=$park['entry_fee'];
        $slot['daily_vehicle_cap']=$park['daily_vehicle_cap'];
        $c=$this->pdo->prepare("SELECT COUNT(*) FROM bookings WHERE slot_id=? AND entry_date=? AND status IN('pending','confirmed')");
        $c->execute([$slotId,$date]);
        $slot['used']=(int)$c->fetchColumn();
        $d=$this->pdo->prepare("SELECT COUNT(*) FROM bookings WHERE park_id=? AND entry_date=? AND status IN('pending','confirmed')");
        $d->execute([$parkId,$date]);
        $slot['daily_used']=(int)$d->fetchColumn();
        return $slot;
    }
}
