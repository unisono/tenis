<?php

namespace App\Application\Actions\Reservation;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Domain\Reservation\ReservationService;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;


class CreateReservationAction
{
    private $reservationService,$logger;

    public function __construct(ReservationService $reservationService,LoggerInterface $LoggerInterface)
    {
        $this->reservationService = $reservationService;
        $this->logger = $LoggerInterface;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        // Obtener datos de la solicitud
        $data = $request->getParsedBody();

        // Validar datos obligatorios
        $courtId = $data['court_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $startTime = $data['start_time'] ?? null;
        $endTime = $data['end_time'] ?? null;

        if (!$courtId || !$userId || !$startTime) {
            throw new HttpBadRequestException($request, 'Todos los campos son obligatorios.');
        }

        // Validar formato de fecha y hora
        if (!strtotime($startTime)) {
            throw new HttpBadRequestException($request, 'El formato de fecha y hora es inválido.');
        }



        try {
            // Crear la reserva a través del servicio
            $reservation = $this->reservationService->createReservation($courtId, $userId, $startTime,$endTime);

            // Responder con la reserva creada
            $response->getBody()->write(json_encode([
                'message' => 'Reserva creada exitosamente.',
                'reservation' => $reservation
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\DomainException $e) {
            // Loguear el error específico de dominio
            $logger = $this->logger; // Asegúrate de que logger esté disponible
            $logger->error('Error de dominio: ' . $e->getMessage());

            $response->getBody()->write(json_encode([
                'error' => $e->getMessage(),
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Exception $e) {
            // Loguear el error genérico
            $logger = $this->logger; // Asegúrate de que logger esté disponible
            $logger->error('Error interno en el servidor: ' . $e->getMessage());

            $response->getBody()->write(json_encode([
                'error' => 'Error interno en el servidor.',
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}