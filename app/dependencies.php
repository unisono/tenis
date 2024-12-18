<?php

use App\Application\Settings\SettingsInterface;
use App\Domain\Reservation\ReservationRepository;
use App\Domain\Reservation\ReservationService;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use App\Domain\Reservation\GetPendingReservationsRepository;
use App\Domain\Reservation\GetPendingReservationsService;




return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);


            return $logger;
        },

        // Registro de la conexión PDO
        PDO::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class)->get('db');
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $settings['host'], $settings['port'],$settings['database']);
            return new PDO($dsn, $settings['username'], $settings['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        },

        // Registro del repositorio de reservas
        ReservationRepository::class => function (ContainerInterface $c) {
            return new ReservationRepository($c->get(PDO::class));
        },

        // Registro del servicio de reservas
        ReservationService::class => function (ContainerInterface $c) {
            return new ReservationService($c->get(ReservationRepository::class));
        },
        GetPendingReservationsRepository::class => function (ContainerInterface $c) {
            return new GetPendingReservationsRepository($c->get(PDO::class));
        },
        GetPendingReservationsService::class => function (ContainerInterface $c) {
            return new GetPendingReservationsService($c->get(GetPendingReservationsRepository::class));
        },
]);
};
