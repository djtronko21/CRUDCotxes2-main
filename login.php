<?php
include 'connexio.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correu = $_POST['correu'];
    $contrasenya = $_POST['contrasenya'];

    $sql = "SELECT * FROM usuaris WHERE correu = '$correu' AND contrasenya = '$contrasenya'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $usuari = $result->fetch_assoc();
        $_SESSION['usuari_id'] = $usuari['id'];
        $_SESSION['nom'] = $usuari['nom'];
        header("Location: principal.php");
        exit;
    } else {
        $error = "Correu o contrasenya incorrectes.";
    }
}
?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Inici de sessió</title>
    <style>
        body { font-family: Arial; background: #f3f4f6; display: flex; align-items: center; justify-content: center; height: 100vh; }
        form { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        input { width: 100%; margin: 10px 0; padding: 8px; }
        button { width: 100%; background: #007bff; color: white; border: none; padding: 10px; cursor: pointer; border-radius: 5px; }
        button:hover { background: #0056b3; }
        p.error { color: red; }
        a { text-decoration: none; color: #007bff; }
    </style>
</head>
<body>
<form method="post">
    <h2>🔐 Inici de sessió</h2>
    <input type="email" name="correu" placeholder="Correu" required>
    <input type="password" name="contrasenya" placeholder="Contrasenya" required>
    <button type="submit">Entrar</button>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <p>No tens compte? <a href="registre.php">Registra't</a></p>
</form>
</body>
</html>
