#!/usr/bin/env php
<?php

if ($argc < 2) {
    echo "Uso: php create-feature.php <namespace/FeatureName>\n";
    exit(1);
}

$feature = $argv[1]; // Ejemplo: reservation/GetPendingReservations
$featureParts = explode('/', $feature);
$namespace = implode('/', array_slice($featureParts, 0, -1)); // Ejemplo: reservation
$featureName = end($featureParts); // Ejemplo: GetPendingReservations

// Directorios base
$basePath = __DIR__;
$domainPath = "$basePath/src/Domain/$namespace";
$appPath = "$basePath/src/Application/Actions/$namespace";
$routesFile = "$basePath/app/routes.php";
$dependenciesFile = "$basePath/app/dependencies.php";

// Crear directorios necesarios
$directories = [$domainPath, $appPath];
foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
        echo "Directorio creado: $directory\n";
    }
}

// Crear archivos de ejemplo
$files = [
    "$domainPath/{$featureName}Repository.php" => "<?php\n\nnamespace App\\Domain\\$namespace;\n\nuse PDO;\n\nclass {$featureName}Repository\n{\n    private \$db;\n\n    public function __construct(PDO \$db)\n    {\n        \$this->db = \$db;\n    }\n\n    public function findPendingReservations(): array\n    {\n        \$query = \"SELECT * FROM reservations WHERE status = 'pending'\";\n        \$stmt = \$this->db->prepare(\$query);\n        \$stmt->execute();\n\n        return \$stmt->fetchAll();\n    }\n}\n",

    "$domainPath/{$featureName}Service.php" => "<?php\n\nnamespace App\\Domain\\$namespace;\n\nuse App\\Domain\\$namespace\\{$featureName}Repository;\n\nclass {$featureName}Service\n{\n    private {$featureName}Repository \$reservationRepository;\n\n    public function __construct({$featureName}Repository \$reservationRepository)\n    {\n        \$this->reservationRepository = \$reservationRepository;\n    }\n\n    public function getPendingReservations(): array\n    {\n        return \$this->reservationRepository->findPendingReservations();\n    }\n}\n",

    "$appPath/{$featureName}Action.php" => "<?php\n\nnamespace App\\Application\\Actions\\$namespace;\n\nuse Psr\\Http\\Message\\ResponseInterface as Response;\nuse Psr\\Http\\Message\\ServerRequestInterface as Request;\nuse App\\Domain\\$namespace\\{$featureName}Service;\nuse Psr\\Log\\LoggerInterface;\n\nclass {$featureName}Action\n{\n    private {$featureName}Service \$reservationService;\n    private LoggerInterface \$logger;\n\n    public function __construct({$featureName}Service \$reservationService, LoggerInterface \$logger)\n    {\n        \$this->reservationService = \$reservationService;\n        \$this->logger = \$logger;\n    }\n\n    public function __invoke(Request \$request, Response \$response): Response\n    {\n        try {\n            // Llamar al servicio para obtener las reservas pendientes\n            \$pendingReservations = \$this->reservationService->getPendingReservations();\n\n            // Responder con las reservas encontradas\n            \$response->getBody()->write(json_encode([\n" .
        "                'status' => 'success',\n" .
        "                'data' => \$pendingReservations\n" .
        "            ]));\n\n            return \$response->withHeader('Content-Type', 'application/json')->withStatus(200);\n        } catch (\\Exception \$e) {\n            \$this->logger->error('Error al obtener reservas pendientes: ' . \$e->getMessage());\n            \$response->getBody()->write(json_encode([\n" .
        "                'status' => 'error',\n" .
        "                'message' => 'Error interno en el servidor.'\n" .
        "            ]));\n\n            return \$response->withHeader('Content-Type', 'application/json')->withStatus(500);\n        }\n    }\n}\n"
];

foreach ($files as $filePath => $content) {
    if (!file_exists($filePath)) {
        file_put_contents($filePath, $content);
        echo "Archivo creado: $filePath\n";
    } else {
        echo "El archivo ya existe: $filePath\n";
    }
}

// Agregar dependencias
if (file_exists($dependenciesFile)) {
    // Leemos el archivo de dependencias
    $dependenciesContent = file_get_contents($dependenciesFile);

    // Definimos las clases `use` para el Repository y el Service
    $repositoryUse = "use App\\Domain\\$namespace\\{$featureName}Repository;\n";
    $serviceUse = "use App\\Domain\\$namespace\\{$featureName}Service;\n";

    // Buscar el último 'use' y el primer ';' en esa línea
    $lastUsePos = strrpos($dependenciesContent, 'use ');
    $semicolonPos = strpos($dependenciesContent, ';', $lastUsePos);

    if ($lastUsePos !== false && $semicolonPos !== false) {
        // Insertar salto de línea después del primer ';' y luego agregar los nuevos 'use'
        $dependenciesContent = substr_replace(
            $dependenciesContent,
            "\n" . $repositoryUse . $serviceUse,
            $semicolonPos + 1,
            0
        );
    }

    // Insertamos las definiciones justo antes del cierre del array `]);`
    $position = strrpos($dependenciesContent, ']);');
    if ($position !== false) {
        $repositoryDefinition = "        {$featureName}Repository::class => function (ContainerInterface \$c) {\n" .
            "            return new {$featureName}Repository(\$c->get(PDO::class));\n" .
            "        },\n";
        $serviceDefinition = "        {$featureName}Service::class => function (ContainerInterface \$c) {\n" .
            "            return new {$featureName}Service(\$c->get({$featureName}Repository::class));\n" .
            "        },\n";

        $updatedContent = substr_replace(
            $dependenciesContent,
            $repositoryDefinition . $serviceDefinition,
            $position,
            0
        );
        file_put_contents($dependenciesFile, $updatedContent);
        echo "Dependencias agregadas a $dependenciesFile\n";
    } else {
        echo "No se encontró el cierre del array en $dependenciesFile. Verifica la estructura del archivo.\n";
    }
} else {
    echo "El archivo no existe en la ruta especificada: $dependenciesFile. Verifica la ruta.\n";
}

echo "Generación de la característica '$feature' finalizada.\n";