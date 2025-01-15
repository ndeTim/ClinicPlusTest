<?php
header('Content-Type: application/json');

// Подключение к базе данных
$host = 'localhost';
$db = 'clinicPlusTest';
$user = 'root';
$password = 'root';
$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Ошибка подключения к базе данных']);
    exit;
}

// Получение данных из запроса
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

// Валидация
if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $phone)) {
    echo json_encode(['success' => false, 'error' => 'Неверные данные формы']);
    exit;
}

// Проверка дубликатов
$stmt = $conn->prepare('SELECT created_at FROM requests WHERE name = ? AND email = ? AND phone = ? ORDER BY created_at DESC LIMIT 1');
$stmt->bind_param('sss', $name, $email, $phone);
$stmt->execute();
$stmt->bind_result($created_at);
if ($stmt->fetch()) {
    $stmt->close();
    $last_submission_time = strtotime($created_at);
    if (time() - $last_submission_time < 300) {
        echo json_encode(['success' => false, 'error' => 'Форма уже была отправлена недавно']);
        exit;
    }
}
$stmt->close();

// Сохранение заявки
$stmt = $conn->prepare('INSERT INTO requests (name, email, phone, created_at) VALUES (?, ?, ?, NOW())');
$stmt->bind_param('sss', $name, $email, $phone);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Ошибка сохранения заявки']);
}
$stmt->close();
$conn->close();
?>
