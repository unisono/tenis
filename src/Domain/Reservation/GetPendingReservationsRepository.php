<?php

namespace App\Domain\Reservation;

use PDO;

class GetPendingReservationsRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findPendingReservations(): array
    {
        $query = "SELECT * FROM reservations WHERE status = 'pending'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
