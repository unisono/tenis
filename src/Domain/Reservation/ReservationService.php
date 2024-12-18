<?php

namespace App\Domain\Reservation;


class ReservationService
{
    private $reservationRepository;

    public function __construct(ReservationRepository $reservationRepository)
    {
        $this->reservationRepository = $reservationRepository;
    }

    public function createReservation($courtId, $userId, $dateTime,$endTime)
    {
        $result =  $this->reservationRepository->create($courtId, $userId, $dateTime,$endTime);
        return $result;
    }
}