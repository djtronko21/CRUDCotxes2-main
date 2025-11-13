<?php
session_start();
require_once __DIR__ . '/../BD/connexio.php';

if (!isset($_SESSION['usuari_id'])) {
    header("Location: ../LoginRegistre/login.php");
    exit();
}

$idUsuari = $_SESSION['usuari_id'];

// Filtres
$filtreOrigen = trim($_GET['origen'] ?? '');
$filtreDestí = trim($_GET['desti'] ?? '');
$filtreData = $_GET['data'] ?? '';
$filtreHora = $_GET['hora'] ?? '';

// Consulta base amb validació
$sql = "SELECT 
    h.id,
    h.data,
    h.hora_inici,
    h.hora_fi,
    h.preu,
    h.comentaris,
    u.nom AS nom_usuari,
    u.id AS usuari_id,
    r.origen,
    r.desti,
    c.marca,
    c.model,
    c.matricula,
    c.imatge,
    h.places_totals
FROM horaris h
INNER JOIN usuaris u ON h.usuari_id = u.id
INNER JOIN rutes r ON h.ruta_id = r.id
LEFT JOIN cotxes c ON h.cotxe_id = c.id
WHERE h.data >= CURDATE()";

$params = array();
$types = "";

if (!empty($filtreOrigen)) {
    $sql .= " AND r.origen LIKE ?";
    $params[] = "%$filtreOrigen%";
    $types .= "s";
}

if (!empty($filtreDestí)) {
    $sql .= " AND r.desti LIKE ?";
    $params[] = "%$filtreDestí%";
    $types .= "s";
}

if (!empty($filtreData)) {
    $sql .= " AND h.data = ?";
    $params[] = $filtreData;
    $types .= "s";
}

if (!empty($filtreHora)) {
    $sql .= " AND h.hora_inici >= ?";
    $params[] = $filtreHora . ":00";
    $types .= "s";
}

$sql .= " ORDER BY h.data ASC, h.hora_inici ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultat = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3838550041681016"
     crossorigin="anonymous"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viatges Disponibles — BlaBlaCash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    
    <!-- 🗺️ Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    
    <style>
        :root { --primary: #4CAF50; --bg: #e0ffff; --text: #333; }
        body { background: var(--bg); font-family: 'Segoe UI', system-ui; color: var(--text); }
        .navbar { background: #333; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .navbar-brand { font-weight: 700; font-size: 1.5rem; }
        .hero { background: linear-gradient(135deg, #4CAF50, #45a049); color: #fff; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; }
        .trip-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        .trip-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        /* 🗺️ Estil del mapa */
        .map-container {
            width: 100%;
            height: 200px;
            border-radius: 12px 12px 0 0;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #e0e0e0, #f0f0f0);
        }
        .map-card {
            width: 100%;
            height: 100%;
        }
        .map-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #666;
            font-size: 0.9rem;
        }
        
        .trip-body { padding: 1.5rem; }
        .trip-route { font-size: 1.3rem; font-weight: 700; color: var(--text); margin-bottom: 0.5rem; }
        .trip-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 1rem 0;
            font-size: 0.95rem;
        }
        .info-item { display: flex; align-items: center; gap: 0.6rem; color: #666; }
        .price { font-size: 1.5rem; font-weight: 700; color: var(--primary); }
        .places-badge {
            display: inline-block;
            background: #e8f5e9;
            color: var(--primary);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .filter-section {
            background: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .btn-reservar {
            background: var(--primary);
            border: none;
            color: #fff;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-reservar:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        .empty-state i { font-size: 3rem; color: #999; margin-bottom: 1rem; }
        
        /* Ocultar controls de routing */
        .leaflet-routing-container { display: none !important; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="principal.php"><i class="fas fa-car-side me-2"></i>BlaBlaCash</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navMenu">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="principal.php"><i class="fas fa-home me-1"></i>Inici</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php"><i class="fas fa-search me-1"></i>Viatges</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['nom']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../editar/afegir.php"><i class="fas fa-plus me-2"></i>Afegir horari</a></li>
                        <li><a class="dropdown-item" href="../editar/editar.php"><i class="fas fa-edit me-2"></i>Editar horaris</a></li>
                        <li><a class="dropdown-item" href="../Perfil/perfil.php"><i class="fas fa-user me-2"></i>Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../LoginRegistre/logout.php"><i class="fas fa-sign-out-alt me-2 text-danger"></i>Tancar sessió</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-4">
    <div class="hero">
        <h1 class="mb-2"><i class="fas fa-search me-2"></i>Busca el teu viatge</h1>
        <p class="mb-0">Troba els millors viatges a compartir disponibles</p>
    </div>

    <!-- Filtres avançats -->
    <div class="filter-section">
        <form method="get" class="row g-2">
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-map-marker-alt me-1"></i>Origen</label>
                <input type="text" name="origen" class="form-control" placeholder="Ciutat" value="<?php echo htmlspecialchars($filtreOrigen); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-location-dot me-1"></i>Destí</label>
                <input type="text" name="desti" class="form-control" placeholder="Ciutat" value="<?php echo htmlspecialchars($filtreDestí); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label"><i class="far fa-calendar me-1"></i>Data</label>
                <input type="date" name="data" class="form-control" value="<?php echo htmlspecialchars($filtreData); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label"><i class="far fa-clock me-1"></i>Hora</label>
                <input type="time" name="hora" class="form-control" value="<?php echo htmlspecialchars($filtreHora); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Cercar</button>
                <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-redo"></i></a>
            </div>
        </form>
    </div>

    <!-- Llistat de viatges -->
    <div class="row">
        <?php if ($resultat->num_rows > 0):
            $mapIndex = 0;
            while ($viatge = $resultat->fetch_assoc()):
                $placesDisponibles = $viatge['places_totals'];
                $mapIndex++;
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="trip-card">
                    <!-- 🗺️ MAPA DINÀMIC -->
                    <div class="map-container">
                        <div class="map-loading"><i class="fas fa-spinner fa-spin me-2"></i>Carregant mapa...</div>
                        <div 
                            id="map-<?php echo $mapIndex; ?>" 
                            class="map-card" 
                            data-origen="<?php echo htmlspecialchars($viatge['origen']); ?>"
                            data-desti="<?php echo htmlspecialchars($viatge['desti']); ?>">
                        </div>
                    </div>

                    <div class="trip-body">
                        <div class="trip-route">
                            <i class="fas fa-arrow-right me-1" style="color: var(--primary);"></i>
                            <?php echo htmlspecialchars($viatge['origen']) . " → " . htmlspecialchars($viatge['desti']); ?>
                        </div>

                        <div class="trip-info">
                            <div class="info-item">
                                <i class="far fa-calendar"></i>
                                <span><?php echo date('d/m/Y', strtotime($viatge['data'])); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="far fa-clock"></i>
                                <span><?php echo substr($viatge['hora_inici'], 0, 5); ?></span>
                            </div>
                            <?php if (!empty($viatge['marca'])): ?>
                                <div class="info-item">
                                    <i class="fas fa-car"></i>
                                    <span><?php echo htmlspecialchars($viatge['marca'] . " " . $viatge['model']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($viatge['nom_usuari']); ?></span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <div>
                                <div class="price">€<?php echo number_format($viatge['preu'] ?? 0, 2); ?></div>
                                <div class="places-badge">
                                    <i class="fas fa-chair me-1"></i><?php echo $viatge['places_totals']; ?> places
                                </div>
                            </div>
                            <button class="btn btn-reservar btn-sm" onclick="reservarViatge(<?php echo $viatge['id']; ?>)">
                                <i class="fas fa-check me-1"></i>Reservar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php
            endwhile;
        else:
        ?>
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No hi ha viatges disponibles</h3>
                    <p class="text-muted">Prova a canviar els filtres o intenta més tard</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 🗺️ Scripts de Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// 🗺️ Geocodificación con cache
const geocodeCache = {};

async function geocode(place) {
    if (geocodeCache[place]) {
        return geocodeCache[place];
    }
    
    try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(place + ', Spain')}`;
        const resp = await fetch(url);
        const data = await resp.json();
        
        if (data && data.length > 0) {
            const coords = [parseFloat(data[0].lat), parseFloat(data[0].lon)];
            geocodeCache[place] = coords;
            return coords;
        }
    } catch (error) {
        console.error('Error geocoding:', error);
    }
    return null;
}

// 🗺️ Dibuja ruta en un mapa
async function drawRoute(mapId, origen, desti) {
    const coordsOr = await geocode(origen);
    const coordsDest = await geocode(desti);
    
    if (!coordsOr || !coordsDest) {
        document.querySelector(`#${mapId}`).innerHTML = '<div style="text-align:center; padding:80px 20px; color:#999;"><i class="fas fa-map-marked-alt" style="font-size:2rem;"></i><br>No s\'ha pogut carregar el mapa</div>';
        return;
    }

    const map = L.map(mapId, { 
        scrollWheelZoom: false,
        dragging: true,
        zoomControl: false
    }).setView(coordsOr, 8);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    // Marcar origen i destí
    L.marker(coordsOr).addTo(map).bindPopup(`<b>Origen:</b> ${origen}`);
    L.marker(coordsDest).addTo(map).bindPopup(`<b>Destí:</b> ${desti}`);

    // Dibuixar ruta
    L.Routing.control({
        waypoints: [
            L.latLng(coordsOr[0], coordsOr[1]),
            L.latLng(coordsDest[0], coordsDest[1])
        ],
        lineOptions: { 
            styles: [{ color: '#4CAF50', weight: 4, opacity: 0.7 }] 
        },
        addWaypoints: false,
        draggableWaypoints: false,
        fitSelectedRoutes: true,
        show: false,
        createMarker: function() { return null; } // No mostrar marcadors de la ruta
    }).addTo(map);

    // Ajustar vista per mostrar tota la ruta
    setTimeout(() => {
        const bounds = L.latLngBounds([coordsOr, coordsDest]);
        map.fitBounds(bounds, { padding: [20, 20] });
    }, 500);
}

// 💤 Lazy load de mapas
document.addEventListener('DOMContentLoaded', () => {
    const maps = document.querySelectorAll('.map-card');
    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const div = entry.target;
                const origen = div.dataset.origen;
                const desti = div.dataset.desti;
                
                // Ocultar loading
                const loading = div.previousElementSibling;
                if (loading) loading.style.display = 'none';
                
                drawRoute(div.id, origen, desti);
                obs.unobserve(div);
            }
        });
    }, { rootMargin: '100px' });

    maps.forEach(mapDiv => observer.observe(mapDiv));
});

function reservarViatge(id) {
    if (confirm('Vols reservar aquest viatge?')) {
        alert('Reserva enviada!');
    }
}
</script>
</body>
</html>