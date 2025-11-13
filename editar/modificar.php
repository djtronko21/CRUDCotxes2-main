<?php
session_start();
require_once __DIR__ . '/../BD/connexio.php';

if (!isset($_SESSION['usuari_id'])) {
    header("Location: ../LoginRegistre/login.php");
    exit();
}

$idUsuari = $_SESSION['usuari_id'];
$error = null;

// Get trip data if ID is provided
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $sql = "SELECT h.*, r.id as ruta_id, c.id as cotxe_id 
            FROM horaris h
            LEFT JOIN rutes r ON h.ruta_id = r.id
            LEFT JOIN cotxes c ON h.cotxe_id = c.id
            WHERE h.id = ? AND h.usuari_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $idUsuari);
    $stmt->execute();
    $viatge = $stmt->get_result()->fetch_assoc();

    if (!$viatge) {
        header("Location: editar.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST['id']);
    $ruta_id = intval($_POST['ruta_id']);
    $cotxe_id = empty($_POST['cotxe_id']) ? null : intval($_POST['cotxe_id']);
    $data = trim($_POST['data']);
    $hora_inici = trim($_POST['hora_inici']);
    $hora_fi = trim($_POST['hora_fi']);
    $comentaris = trim($_POST['comentaris']);

    // Validate the trip belongs to the user
    $check = $conn->prepare("SELECT id FROM horaris WHERE id = ? AND usuari_id = ?");
    $check->bind_param("ii", $id, $idUsuari);
    $check->execute();
    
    if ($check->get_result()->num_rows === 1) {
        $sql = "UPDATE horaris SET 
                ruta_id = ?, 
                cotxe_id = ?, 
                data = ?, 
                hora_inici = ?, 
                hora_fi = ?, 
                comentaris = ?
                WHERE id = ? AND usuari_id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissssii", 
            $ruta_id, 
            $cotxe_id, 
            $data, 
            $hora_inici, 
            $hora_fi, 
            $comentaris,
            $id,
            $idUsuari
        );

        if ($stmt->execute()) {
            header("Location: editar.php");
            exit();
        } else {
            $error = "Error al actualitzar el viatge";
        }
    }
}

// Get available routes and cars for the form
$rutes = $conn->query("SELECT id, origen, desti FROM rutes ORDER BY origen");
$cotxes = $conn->prepare("SELECT id, marca, model, matricula FROM cotxes WHERE usuari_id = ?");
$cotxes->bind_param("i", $idUsuari);
$cotxes->execute();
$cotxesResult = $cotxes->get_result();
?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3838550041681016"
     crossorigin="anonymous"></script>
    <meta charset="UTF-8">
    <title>Modificar Viatge</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        body {
            background-color: #e0ffff;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .main-header {
            background: #333;
            padding: 1rem;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            position: relative;
        }

        .logo {
            margin-right: auto;
        }

        .logo a {
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
            transition: color 0.3s;
        }

        .logo a:hover {
            color: #4CAF50;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .btn {
            background: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }

        .btn:hover {
            background: #45a049;
        }

        .btn-cancel {
            background: #666;
        }

        .btn-cancel:hover {
            background: #555;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="logo">
            <a href="../Menu/index.php">BlaBlaCash</a>
        </div>
    </header>

    <div class="container">
        <h1>✏️ Modificar Viatge</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="id" value="<?php echo $viatge['id']; ?>">
            
            <div class="form-group">
                <label for="ruta_id">Ruta:</label>
                <select name="ruta_id" id="ruta_id" required>
                    <?php while ($r = $rutes->fetch_assoc()): ?>
                        <option value="<?php echo $r['id']; ?>" 
                            <?php echo ($r['id'] == $viatge['ruta_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['origen'] . " → " . $r['desti']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="cotxe_id">Cotxe (opcional):</label>
                <select name="cotxe_id" id="cotxe_id">
                    <option value="">-- Cap cotxe --</option>
                    <?php while ($c = $cotxesResult->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>"
                            <?php echo ($c['id'] == $viatge['cotxe_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['marca'] . " " . $c['model'] . " (" . $c['matricula'] . ")"); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="data">Data:</label>
                <input type="date" name="data" id="data" 
                    value="<?php echo htmlspecialchars($viatge['data']); ?>" required>
            </div>

            <div class="form-group">
                <label for="hora_inici">Hora Inici:</label>
                <input type="time" name="hora_inici" id="hora_inici" 
                    value="<?php echo htmlspecialchars($viatge['hora_inici']); ?>" required>
            </div>

            <div class="form-group">
                <label for="hora_fi">Hora Fi:</label>
                <input type="time" name="hora_fi" id="hora_fi" 
                    value="<?php echo htmlspecialchars($viatge['hora_fi']); ?>">
            </div>

            <div class="form-group">
                <label for="comentaris">Comentaris:</label>
                <textarea name="comentaris" id="comentaris" rows="3"><?php echo htmlspecialchars($viatge['comentaris']); ?></textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="btn">💾 Guardar Canvis</button>
                <a href="editar.php" class="btn btn-cancel">❌ Cancel·lar</a>
            </div>
        </form>
    </div>
</body>
</html>