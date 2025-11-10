<?php
include 'connexio.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = $_POST['nom'];
    $correu = $_POST['correu'];
    $contrasenya = $_POST['contrasenya'];

    $sql = "INSERT INTO usuaris (nom, correu, contrasenya) VALUES ('$nom', '$correu', '$contrasenya')";
    if ($conn->query($sql)) {
        header("Location: login.php");
        exit;
    } else {
        $error = "Error al registrar: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Registre</title>
    <style>
        body { font-family: Arial; background: #1d9ad3ff; display: flex; align-items: center; justify-content: center; height: 100vh; }
        form { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        input { width: 100%; margin: 10px 0; padding: 8px; }
        button { width: 100%; background: #28a745; color: white; border: none; padding: 10px; cursor: pointer; border-radius: 5px; }
        button:hover { background: #218838; }
        p.error { color: red; }
        a { text-decoration: none; color: #007bff; }
    </style>
</head>
<body>
<form method="post">
    <h2>📝 Registre</h2>
    <input type="text" name="nom" placeholder="Nom complet" required>
    <input type="email" name="correu" placeholder="Correu" required>
    <input type="password" name="contrasenya" placeholder="Contrasenya" required>
    <button type="submit">Registrar-se</button>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <p>Ja tens compte? <a href="login.php">Inicia sessió</a></p>
</form>
</body>
</html>
