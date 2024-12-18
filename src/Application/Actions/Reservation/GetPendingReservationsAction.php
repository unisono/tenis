<?php

namespace App\Application\Actions\Reservation;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Domain\Reservation\GetPendingReservationsService;
use Psr\Log\LoggerInterface;

class GetPendingReservationsAction
{
    private GetPendingReservationsService $reservationService;
    private LoggerInterface $logger;

    public function __construct(GetPendingReservationsService $reservationService, LoggerInterface $logger)
    {
        $this->reservationService = $reservationService;
        $this->logger = $logger;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        try {
            // Llamar al servicio para obtener las reservas pendientes
            $pendingReservations = $this->reservationService->getPendingReservations();

            // Responder con las reservas encontradas
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $pendingReservations
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error('Error al obtener reservas pendientes: ' . $e->getMessage());
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Error interno en el servidor.'
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
