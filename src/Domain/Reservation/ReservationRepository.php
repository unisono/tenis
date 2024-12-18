<?php

namespace App\Domain\Reservation;

use PDO;

class ReservationRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create($courtId, $userId, $startTime,$endTime)
    {
        $query = "INSERT INTO reservations (court_id, user_id, start_time,end_time, status) 
                  VALUES (:court_id, :user_id, :start_time,:end_time, 'pending')";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':court_id' => $courtId,
            ':user_id' => $userId,
            ':start_time' => $startTime,
            ':end_time' => $endTime,
        ]);

        $result = [
            'reservation_id' => $this->db->lastInsertId(),
            'court_id' => $courtId,
            'user_id' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'pending',
        ];
        return $result;
    }
}