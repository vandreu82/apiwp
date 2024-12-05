<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['mensaje' => 'Método no permitido.']);
    exit;
}

// Decodificar el JSON recibido
$input = json_decode(file_get_contents('php://input'), true);

// Configuración de la API de WordPress
$dominio = 'http://wordpress.terminus.lan'; // Cambia esto si es diferente
$usuario = 'salvor'; // Usuario del perfil de WordPress
$api_key = 'hchG LZJ2 BIaK fVwF OeeY jlD2'; // Clave generada en el perfil
$auth = base64_encode("$usuario:$api_key");

// Función para publicar en WordPress
function publicarEnWordpress($titulo, $contenido, $dominio, $auth)
{
    $data = [
        'title'   => $titulo,
        'content' => $contenido,
        'status'  => 'publish'
    ];

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

    if ($http_code === 201) {
        return ['mensaje' => 'Publicación creada con éxito.'];
    } else {
        return [
            'mensaje' => 'Error al publicar en WordPress.',
            'detalles' => json_decode($response, true)
        ];
    }
}

// Si se proporciona un título y contenido, publicarlos directamente
if (isset($input['titulo']) && isset($input['contenido'])) {
    $resultado = publicarEnWordpress($input['titulo'], $input['contenido'], $dominio, $auth);
    echo json_encode($resultado);
    exit;
}

// Si no hay título y contenido, obtener información del tiempo
$url = 'https://www.el-tiempo.net/api/json/v2/provincias/30';

// Consultar la API del Tiempo
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($http_code !== 200 || empty($response)) {
    http_response_code(500);
    echo json_encode([
        'mensaje' => 'Error al consultar la API del Tiempo.',
        'http_code' => $http_code,
        'response' => $response ?? 'Respuesta vacía'
    ]);
    exit;
}


$datos = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'mensaje' => 'Respuesta no válida del servicio externo.',
        'error' => json_last_error_msg()
    ]);
    exit;
}

// Filtrar datos para la ciudad de Murcia
$id_deseado = '30030'; // ID de la ciudad de Murcia
$ciudad = null;

foreach ($datos['ciudades'] as $item) {
    if ($item['id'] === $id_deseado) {
        $ciudad = $item;
        break;
    }
}

if (!$ciudad) {
    http_response_code(404);
    echo json_encode(['mensaje' => 'Datos no encontrados para la ciudad especificada.']);
    exit;
}

// Preparar contenido para WordPress
$titulo = "El Tiempo en " . $ciudad['name'];
$contenido = "Hoy: " . $datos['today']['p'] . "\n\n" .
    "Estado del cielo: " . $ciudad['stateSky']['description'] . "\n" .
    "Temperatura Máxima: " . $ciudad['temperatures']['max'] . "°C\n" .
    "Temperatura Mínima: " . $ciudad['temperatures']['min'] . "°C";

$resultado = publicarEnWordpress($titulo, $contenido, $dominio, $auth);
echo json_encode($resultado);
