<?php
$host = 'cd '; // Или IP-адрес сервера, например, '192.168.1.100'
$dbname = 'metallzh_test';
$username = 'metallzh_test';
$password = 'Zhopakota337!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    file_put_contents(__DIR__ . '/debug.log', "Успешное  к БД\n", FILE_APPEND);
} catch (PDOException $e) {
    file_put_contents(__DIR__ . '/debug.log', "Ошибка подключения к БД: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Ошибка подключения к БД: " . $e->getMessage()
    ]);
    exit;
}
?>