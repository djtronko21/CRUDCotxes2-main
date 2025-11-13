<?php
session_start();
include __DIR__ . '/../BD/connexio.php';

$error = null;
$success = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correu = trim($_POST['correu'] ?? '');
    $contrasenya = $_POST['contrasenya'] ?? '';

    // Validacions
    if (empty($correu) || empty($contrasenya)) {
        $error = "Completa tots els camps.";
    } elseif (!filter_var($correu, FILTER_VALIDATE_EMAIL)) {
        $error = "Format de correu invàlid.";
    } else {
        $sql = "SELECT id, nom, contrasenya FROM usuaris WHERE correu = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = "Error en la consulta.";
        } else {
            $stmt->bind_param("s", $correu);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $usuari = $result->fetch_assoc();
                if (password_verify($contrasenya, $usuari['contrasenya'])) {
                    $_SESSION['usuari_id'] = $usuari['id'];
                    $_SESSION['nom'] = $usuari['nom'];
                    header("Location: ../Menu/principal.php");
                    exit;
                } else {
                    $error = "Credencials incorrectes.";
                }
            } else {
                $error = "L'usuari no existeix.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3838550041681016"
     crossorigin="anonymous"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inici de sessió — BlaBlaCash</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, Arial;
            background: linear-gradient(135deg, #e0ffff 0%, #b3f0ff 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .brand {
            text-align: center;
            margin-bottom: 2.5rem;
            animation: slideDown 0.6s ease;
        }
        .brand h1 {
            font-size: 3.5rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 800;
        }
        .brand p {
            color: #666;
            font-size: 1.1rem;
            font-weight: 500;
        }
        .container {
            background: #fff;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 420px;
            animation: slideUp 0.6s ease;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.6rem;
            color: #333;
            font-weight: 600;
            font-size: 0.95rem;
        }
        input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        .error {
            color: #dc3545;
            background: #fff5f5;
            border-left: 4px solid #dc3545;
            padding: 12px 14px;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        button {
            width: 100%;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(76, 175, 80, 0.4);
        }
        button:active {
            transform: translateY(0);
        }
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }
        .register-link a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .register-link a:hover {
            color: #45a049;
            text-decoration: underline;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        .input-icon input {
            padding-left: 42px;
        }
    </style>
</head>
<body>
    <div class="brand">
        <h1><i class="fas fa-car-side"></i> BlashBlashCarsh</h1>
        <p>Comparteix el teu viatge i estalvia diners</p>
    </div>

    <div class="container">
        <h2 style="text-align: center; margin-bottom: 1.8rem; color: #333; font-size: 1.6rem;">
            <i class="fas fa-sign-in-alt"></i> Iniciar Sessió
        </h2>

        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="form-group">
                <label for="correu">Correu electrònic</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="correu" name="correu" placeholder="exemple@correu.com" required>
                </div>
            </div>

            <div class="form-group">
                <label for="contrasenya">Contrasenya</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="contrasenya" name="contrasenya" placeholder="La teva contrasenya" required>
                </div>
            </div>

            <button type="submit">
                <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sessió
            </button>
        </form>

        <div class="register-link">
            No tens compte? <a href="registre.php"><i class="fas fa-user-plus"></i> Registra't aquí</a>
        </div>
    </div>
</body>
</html>