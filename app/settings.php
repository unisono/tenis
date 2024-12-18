<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {
    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'db' => [
                    'host' => 'host.docker.internal',
                    'port' => 3307,
                    'database' => 'tennis_db',
                    'username' => 'root',
                    'password' => 'root_password',
                ],
                'displayErrorDetails' => true, // Should be set to false in production
                'logError'            => true,
                'logErrorDetails'     => true,
                'logger' => [
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],
            ]);
        },

        // Registro de la conexión PDO con manejo de errores
        PDO::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class)->get('db');
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $settings['host'], $settings['port'],$settings['database']);

            try {
                $pdo = new PDO($dsn, $settings['username'], $settings['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                return $pdo;
            } catch (PDOException $e) {
                // Loguear el error de conexión a la base de datos
                $logger = $c->get(LoggerInterface::class);
                $logger->error('Error de conexión a la base de datos: ' . $e->getMessage());
                throw new \Exception('Error al conectar con la base de datos.');
            }
        },

        // Registro del repositorio de reservas
        ReservationRepository::class => function (ContainerInterface $c) {
            return new ReservationRepository($c->get(PDO::class));
        },

        // Registro del servicio de reservas
        ReservationService::class => function (ContainerInterface $c) {
            return new ReservationService($c->get(ReservationRepository::class));
        },
    ]);
};
