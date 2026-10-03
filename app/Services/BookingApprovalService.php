<?php
namespace WildTrail\Services;
use PDO;
use WildTrail\Exceptions\BookingException;

final class BookingApprovalService {
    public function __construct(private PDO $pdo) {}

    public function approve(int $bookingId, int $adminId): void {
        $this->pdo->beginTransaction();
        try {
            $q=$this->pdo->prepare("SELECT b.*,ps.vehicle_cap FROM bookings b JOIN park_slots ps ON ps.id=b.slot_id WHERE b.id=? FOR UPDATE");
            $q->execute([$bookingId]);
            $b=$q->fetch();
            if(!$b) throw new BookingException('Booking request not found.');
            if($b['status']==='cancelled' || $b['approval_status']==='rejected') throw new BookingException('This request can no longer be approved.');
            if($b['payment_status']==='paid') throw new BookingException('This booking is already paid.');
            if(!$b['vehicle_id'] || !$b['guide_id'] || !$b['driver_id']) throw new BookingException('A vehicle, driver and guide must be assigned before approval.');
            if($b['entry_date'] < date('Y-m-d')) throw new BookingException('Past safari dates cannot be approved.');

            $v=$this->pdo->prepare("SELECT COUNT(*) FROM bookings WHERE id<>? AND vehicle_id=? AND entry_date=? AND slot_id=? AND status<>'cancelled' AND approval_status<>'rejected'");
            $v->execute([$bookingId,$b['vehicle_id'],$b['entry_date'],$b['slot_id']]);
            if((int)$v->fetchColumn()>0) throw new BookingException('The selected vehicle is no longer available for this safari time.');

            $g=$this->pdo->prepare("SELECT COUNT(*) FROM bookings WHERE id<>? AND guide_id=? AND entry_date=? AND slot_id=? AND status<>'cancelled' AND approval_status<>'rejected'");
            $g->execute([$bookingId,$b['guide_id'],$b['entry_date'],$b['slot_id']]);
            if((int)$g->fetchColumn()>0) throw new BookingException('The selected guide is no longer available for this safari time.');

            $cap=$this->pdo->prepare("SELECT COUNT(*) FROM bookings WHERE id<>? AND park_id=? AND slot_id=? AND entry_date=? AND status<>'cancelled' AND approval_status<>'rejected'");
            $cap->execute([$bookingId,$b['park_id'],$b['slot_id'],$b['entry_date']]);
            if((int)$cap->fetchColumn() >= (int)$b['vehicle_cap']) throw new BookingException('The park safari time has reached its vehicle capacity.');

            $this->pdo->prepare("UPDATE bookings SET approval_status='approved',approved_by=?,approved_at=NOW(),rejection_reason=NULL,driver_response='accepted' WHERE id=?")
                ->execute([$adminId,$bookingId]);
            $this->notify((int)$b['visitor_id'], 'Booking '.$b['booking_code'].' approved. Payment is now required to confirm your safari.');
            $this->notify((int)$b['guide_id'], 'Booking '.$b['booking_code'].' was approved by operations for '.$b['entry_date'].'.');
            $this->notify((int)$b['driver_id'], 'Booking '.$b['booking_code'].' was approved by operations for '.$b['entry_date'].'.');
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if($this->pdo->inTransaction()) $this->pdo->rollBack();
            if($e instanceof BookingException) throw $e;
            throw new BookingException('Approval could not be completed.',0,$e);
        }
    }

    public function reject(int $bookingId, int $adminId, string $reason): void {
        $reason=trim($reason);
        if($reason==='') $reason='Resource or operational availability could not be confirmed.';
        if(mb_strlen($reason)>255) $reason=mb_substr($reason,0,255);
        $this->pdo->beginTransaction();
        try {
            $q=$this->pdo->prepare("SELECT * FROM bookings WHERE id=? FOR UPDATE");
            $q->execute([$bookingId]);
            $b=$q->fetch();
            if(!$b) throw new BookingException('Booking request not found.');
            if($b['payment_status']==='paid') throw new BookingException('A paid booking cannot be rejected through the approval queue.');
            if($b['status']==='cancelled') throw new BookingException('This request was already cancelled by the tourist.');
            $this->pdo->prepare("UPDATE bookings SET approval_status='rejected',approved_by=?,approved_at=NOW(),rejection_reason=? WHERE id=?")
                ->execute([$adminId,$reason,$bookingId]);
            $this->notify((int)$b['visitor_id'], 'Booking '.$b['booking_code'].' was not approved: '.$reason);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if($this->pdo->inTransaction()) $this->pdo->rollBack();
            if($e instanceof BookingException) throw $e;
            throw new BookingException('Rejection could not be completed.',0,$e);
        }
    }

    private function notify(int $userId,string $message): void {
        $this->pdo->prepare('INSERT INTO notifications(user_id,message) VALUES(?,?)')->execute([$userId,$message]);
    }
}
