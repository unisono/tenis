<?php

namespace App\Domain\Reservation;

use App\Domain\Reservation\GetPendingReservationsRepository;

class GetPendingReservationsService
{
    private GetPendingReservationsRepository $reservationRepository;

    public function __construct(GetPendingReservationsRepository $reservationRepository)
    {
        $this->reservationRepository = $reservationRepository;
    }

    public function getPendingReservations(): array
    {
        return $this->reservationRepository->findPendingReservations();
    }
}
