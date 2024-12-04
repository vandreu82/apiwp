<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['mensaje' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['titulo']) || !isset($input['contenido'])) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'Faltan datos en la solicitud.']);
    exit;
}

// Configuración de la API de WordPress
$dominio = 'http://wordpress.terminus.lan'; // Cambia esto si es diferente
$usuario = 'salvor'; // Usuario del perfil de WordPress
$api_key = 'hchG LZJ2 BIaK fVwF OeeY jlD2'; // Clave generada en el perfil
$auth = base64_encode("$usuario:$api_key");

// Datos a enviar a WordPress
$data = [
    'title'   => $input['titulo'],
    'content' => $input['contenido'],
    'status'  => 'publish'
];

// Configuración de cURL
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "$dominio/wp-json/wp/v2/posts/",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        "Authorization: Basic $auth"
    ],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// Manejo de la respuesta
if ($http_code === 201) {
    echo json_encode(['mensaje' => 'Publicación creada con éxito.']);
} else {
    echo json_encode([
        'mensaje' => 'Error al publicar en WordPress.',
        'detalles' => json_decode($response, true)
    ]);
}