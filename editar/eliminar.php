<?php
session_start();
require_once __DIR__ . '/../BD/connexio.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuari_id'])) {
    echo json_encode(['success' => false, 'message' => 'No has iniciat sessió']);
    exit();
}

$idUsuari = $_SESSION['usuari_id'];

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no especificat']);
    exit();
}

$id = intval($_GET['id']);

// Verificar que el horario pertenece al usuario actual
$sql = "SELECT id FROM horaris WHERE id = ? AND usuari_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $idUsuari);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'No pots eliminar aquest horari']);
    exit();
}

// Si pasa la validación, eliminarlo
$delete = $conn->prepare("DELETE FROM horaris WHERE id = ? AND usuari_id = ?");
$delete->bind_param("ii", $id, $idUsuari);

if ($delete->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar l\'horari']);
}
?>