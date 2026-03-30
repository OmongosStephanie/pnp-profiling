<?php
// barangay_map.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get barangay from URL
$barangay = isset($_GET['barangay']) ? $_GET['barangay'] : '';

if (empty($barangay)) {
    header("Location: barangays.php");
    exit();
}

// Get basic stats for the barangay
$statsQuery = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN date_time_place_of_arrest IS NOT NULL AND date_time_place_of_arrest != '' THEN 1 ELSE 0 END) as arrested_count
               FROM biographical_profiles 
               WHERE present_address LIKE :barangay";
$statsStmt = $db->prepare($statsQuery);
$barangayParam = '%' . $barangay . '%';
$statsStmt->bindParam(':barangay', $barangayParam);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Barangay Map - <?php echo htmlspecialchars($barangay); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Leaflet CSS for maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f4f7fb;
            color: #1e293b;
            line-height: 1.5;
        }

        /* Side Menu Styles */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #0a2f4d 0%, #123b5e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-logo {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 2px solid #c9a959;
        }

        .sidebar-logo i {
            font-size: 32px;
            color: #c9a959;
        }

        .sidebar-header h3 {
            font-size: 18px;
            margin: 0 0 5px;
            font-weight: 600;
        }

        .sidebar-header p {
            font-size: 12px;
            color: #b0c4de;
            margin: 0;
        }

        .user-info-sidebar {
            background: rgba(255,255,255,0.1);
            margin: 20px;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
        }

        .user-avatar-sidebar {
            width: 60px;
            height: 60px;
            background: #c9a959;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: #0a2f4d;
            font-weight: 600;
            font-size: 24px;
        }

        .user-name-sidebar {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .user-rank-sidebar {
            font-size: 12px;
            color: #b0c4de;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin: 5px 15px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #e0e0e0;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .sidebar-nav a i {
            width: 20px;
            font-size: 16px;
        }

        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .sidebar-nav a.active {
            background: #c9a959;
            color: #0a2f4d;
        }

        .sidebar-nav a.active i {
            color: #0a2f4d;
        }

        /* Main Content */
        .main-content-wrapper {
            flex: 1;
            margin-left: 280px;
            transition: margin-left 0.3s ease;
        }

        /* Top Navbar */
        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #0a2f4d;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .menu-toggle:hover {
            background: #f1f5f9;
        }

        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: #0a2f4d;
            margin: 0;
        }

        .top-user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .top-user-avatar {
            width: 40px;
            height: 40px;
            background: #c9a959;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a2f4d;
            font-weight: 600;
            font-size: 16px;
        }

        .top-user-name {
            font-weight: 500;
            font-size: 14px;
            color: #1e293b;
        }

        .top-user-rank {
            font-size: 12px;
            color: #64748b;
        }

        .main-content {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title-section i {
            font-size: 28px;
            color: #c9a959;
            background: #0a2f4d;
            padding: 12px;
            border-radius: 12px;
        }

        .title-text h2 {
            font-size: 24px;
            font-weight: 600;
            color: #0a2f4d;
            margin: 0;
        }

        .title-text p {
            color: #64748b;
            margin: 0;
            font-size: 13px;
        }

        .btn-back {
            background: #64748b;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-back:hover {
            background: #475569;
            color: white;
        }

        .map-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #c9a959;
            position: relative;
        }

        #barangayMap {
            height: 550px;
            width: 100%;
            border-radius: 12px;
            z-index: 1;
        }

        /* Layer Control Dropdown Styling */
        .layer-control {
            position: absolute;
            top: 30px;
            right: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            padding: 8px 12px;
            min-width: 180px;
        }

        .layer-control label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 5px;
            display: block;
        }

        .layer-control select {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 12px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }

        .layer-control select:hover {
            border-color: #c9a959;
        }

        .layer-control select:focus {
            outline: none;
            border-color: #c9a959;
            box-shadow: 0 0 0 2px rgba(201, 169, 89, 0.1);
        }

        .info-bar {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            padding: 10px 15px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .info-item i {
            color: #c9a959;
        }

        .footer {
            background: white;
            padding: 20px 0;
            margin-top: 40px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        .search-box {
            position: absolute;
            top: 30px;
            left: 30px;
            z-index: 1000;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            padding: 5px;
        }

        .search-box input {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            width: 250px;
            font-size: 12px;
        }

        .search-box button {
            padding: 8px 12px;
            background: #0a2f4d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content-wrapper {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .top-user-info {
                display: none;
            }
            
            #barangayMap {
                height: 400px;
            }
            
            .info-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .layer-control {
                top: 70px;
                right: 10px;
                min-width: 150px;
            }
            
            .search-box {
                top: 70px;
                left: 10px;
            }
            
            .search-box input {
                width: 180px;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .title-text h2 {
                font-size: 20px;
            }
            
            .page-title-section i {
                font-size: 20px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>
<div class="app-wrapper">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3>PNP Profiling System</h3>
            <p>Manolo Fortich Police Station</p>
        </div>
        
        <div class="user-info-sidebar">
            <div class="user-avatar-sidebar">
                <?php echo substr($_SESSION['full_name'], 0, 1); ?>
            </div>
            <div class="user-name-sidebar"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            <div class="user-rank-sidebar"><?php echo htmlspecialchars($_SESSION['rank']); ?> • <?php echo htmlspecialchars($_SESSION['unit']); ?></div>
        </div>
        
        <ul class="sidebar-nav">
            <li>
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="barangays.php" class="active">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Barangays</span>
                </a>
            </li>
            <li>
                <a href="reports.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <li>
                <a href="users.php">
                    <i class="fas fa-users-cog"></i>
                    <span>Accounts</span>
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="page-title">Barangay Map</h2>
            <div class="top-user-info">
                <div>
                    <div class="top-user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                    <div class="top-user-rank"><?php echo htmlspecialchars($_SESSION['rank']); ?></div>
                </div>
                <div class="top-user-avatar">
                    <?php echo substr($_SESSION['full_name'], 0, 1); ?>
                </div>
            </div>
        </div>

        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title-section">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="title-text">
                        <h2><?php echo htmlspecialchars($barangay); ?></h2>
                        <p>Interactive Map - Search and explore the area</p>
                    </div>
                </div>
                <a href="barangays.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Barangays
                </a>
            </div>

            <!-- Map Container -->
            <div class="map-container">
                <div id="barangayMap"></div>
                <div class="info-bar">
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><strong><?php echo htmlspecialchars($barangay); ?></strong>, Manolo Fortich, Bukidnon</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-users"></i>
                        <span>Total Profiles: <strong><?php echo $stats['total']; ?></strong></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-gavel"></i>
                        <span>Arrest Records: <strong><?php echo $stats['arrested_count']; ?></strong></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-info-circle"></i>
                        <span>Use search to find specific locations</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <p>© <?php echo date('Y'); ?> Philippine National Police - Manolo Fortich Police Station. All rights reserved.</p>
                <p>Department of the Interior and Local Government | Bukidnon Police Provincial Office</p>
            </div>
        </footer>
    </div>
</div>

<script>
    // Sidebar Toggle for Mobile
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('open');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnToggle = menuToggle.contains(event.target);
        
        if (!isClickInsideSidebar && !isClickOnToggle && window.innerWidth <= 768 && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
        }
    });

    // Close sidebar on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
        }
    });

    // Get the selected barangay
    let barangayName = '<?php echo addslashes($barangay); ?>';
    
    // Coordinates for each barangay (approximate center)
    const barangayCenters = {
        'Tankulan (Poblacion)': [8.36637900, 124.86443200],
        'San Miguel': [8.38904800, 124.83593600],
        'Lingion': [8.40319400, 124.88830300],
        'Mantibugao': [8.45850000, 124.82408400],
        'Alae': [8.42239400, 124.81303000],
        'Damilag': [8.35332400, 124.81329400],
        'Kalugmanan': [8.27723500, 124.86140300],
        'Lindaban': [8.28964300, 124.84700500],
        'Dalirig': [8.37639600, 124.90117600],
        'Dicklum': [8.37223500, 124.84915600],
        'Lunocan': [8.43158700, 124.84030900],
        'Maluko': [8.37517300, 124.95558900],
        'Sankanan': [8.31593200, 124.85791300],
        'Santiago': [8.43630800, 124.99578200],
        'Santo Niño': [8.42842000, 124.86404200],
        'Ticala': [8.34018700, 124.89189100],
        'Agusan Canyon': [8.33375600, 124.81538500],
        'Dahilayan': [8.21923800, 124.85209300],
        'Guilang-guilang': [8.45752100, 125.04109100],
        'Mambatangan': [8.46782200, 124.79061900],
        'Mampayag': [8.4495, 124.8413],
        'Minsuro': [8.51025300, 124.83125900]
    };

    // Get coordinates for the selected barangay
    let coordinates = barangayCenters[barangayName] || [8.3699, 124.8647];

    // Define map layers
    const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    });

    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
        maxZoom: 19
    });

    const detailedStreetLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO',
        maxZoom: 19
    });

    const topoLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        attribution: 'Map data: &copy; OpenStreetMap contributors, SRTM | Map style: &copy; OpenTopoMap (CC-BY-SA)',
        maxZoom: 17
    });

    // Initialize the map
    const map = L.map('barangayMap').setView(coordinates, 14);
    let currentLayer = streetLayer;
    currentLayer.addTo(map);

    // Try to fetch barangay boundary from OpenStreetMap
    let barangayPolygon = null;
    const searchQuery = `${barangayName}, Manolo Fortich, Bukidnon, Philippines`;
    
    fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(searchQuery)}&format=json&polygon_geojson=1&limit=1`)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0 && data[0].geojson) {
                const geojson = data[0].geojson;
                if (geojson.coordinates) {
                    let coords = geojson.coordinates;
                    if (geojson.type === 'Polygon') {
                        coords = coords[0].map(coord => [coord[1], coord[0]]);
                    } else if (geojson.type === 'MultiPolygon') {
                        coords = coords[0][0].map(coord => [coord[1], coord[0]]);
                    }
                    
                    barangayPolygon = L.polygon(coords, {
                        color: '#c9a959',
                        fillColor: '#0a2f4d',
                        fillOpacity: 0.25,
                        weight: 3,
                        opacity: 0.8
                    }).addTo(map);
                    
                    map.fitBounds(barangayPolygon.getBounds());
                    
                    const center = barangayPolygon.getBounds().getCenter();
                    L.marker(center, {
                        icon: L.divIcon({
                            className: 'barangay-label',
                            html: `<div style="background: rgba(10, 47, 77, 0.85); color: white; padding: 5px 14px; border-radius: 25px; font-size: 13px; font-weight: 600; border: 1px solid #c9a959; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">${barangayName}</div>`,
                            iconSize: [160, 35],
                            iconAnchor: [80, 18]
                        })
                    }).addTo(map);
                    
                    barangayPolygon.bindPopup(`
                        <strong>${barangayName}</strong><br>
                        Manolo Fortich, Bukidnon<br>
                        Total Profiles: <?php echo $stats['total']; ?><br>
                        Arrest Records: <?php echo $stats['arrested_count']; ?>
                    `);
                }
            } else {
                L.circle(coordinates, {
                    color: '#c9a959',
                    fillColor: '#0a2f4d',
                    fillOpacity: 0.2,
                    radius: 1500,
                    weight: 2
                }).addTo(map);
                
                L.marker(coordinates).addTo(map).bindPopup(`
                    <strong>${barangayName}</strong><br>
                    Manolo Fortich, Bukidnon<br>
                    Total Profiles: <?php echo $stats['total']; ?><br>
                    Arrest Records: <?php echo $stats['arrested_count']; ?>
                `).openPopup();
            }
        })
        .catch(error => {
            console.log('Error fetching boundary:', error);
            L.circle(coordinates, {
                color: '#c9a959',
                fillColor: '#0a2f4d',
                fillOpacity: 0.2,
                radius: 1500,
                weight: 2
            }).addTo(map);
            
            L.marker(coordinates).addTo(map).bindPopup(`
                <strong>${barangayName}</strong><br>
                Manolo Fortich, Bukidnon<br>
                Total Profiles: <?php echo $stats['total']; ?><br>
                Arrest Records: <?php echo $stats['arrested_count']; ?>
            `).openPopup();
        });

    // Add search control
    const searchControl = L.Control.geocoder({
        defaultMarkGeocode: false,
        position: 'topleft',
        placeholder: 'Search location...',
        errorMessage: 'Location not found'
    }).on('markgeocode', function(e) {
        const bbox = e.geocode.bbox;
        const center = e.geocode.center;
        L.marker(center).addTo(map).bindPopup(e.geocode.name).openPopup();
        map.fitBounds(bbox);
    }).addTo(map);

    // Create dropdown layer control
    const layerControlDiv = document.createElement('div');
    layerControlDiv.className = 'layer-control';
    layerControlDiv.innerHTML = `
        <label><i class="fas fa-layer-group"></i> Map Layer</label>
        <select id="mapLayerSelect">
            <option value="street" selected>🗺️ Street View</option>
            <option value="satellite">🛰️ Satellite (See Houses)</option>
            <option value="detailed">🏢 Detailed Street</option>
            <option value="topo">⛰️ Topographic</option>
        </select>
    `;
    document.querySelector('.map-container').appendChild(layerControlDiv);

    // Layer switching functionality
    const layers = {
        'street': streetLayer,
        'satellite': satelliteLayer,
        'detailed': detailedStreetLayer,
        'topo': topoLayer
    };

    const layerSelect = document.getElementById('mapLayerSelect');
    layerSelect.addEventListener('change', function() {
        const layerType = this.value;
        map.removeLayer(currentLayer);
        currentLayer = layers[layerType];
        currentLayer.addTo(map);
        
        // Show notification
        const infoBar = document.querySelector('.info-bar');
        const notification = document.createElement('div');
        notification.className = 'info-item';
        notification.style.background = '#0a2f4d';
        notification.style.color = 'white';
        notification.style.padding = '5px 12px';
        notification.style.borderRadius = '20px';
        
        let layerName = '';
        if (layerType === 'street') layerName = 'Street View';
        else if (layerType === 'satellite') layerName = 'Satellite';
        else if (layerType === 'detailed') layerName = 'Detailed Street';
        else if (layerType === 'topo') layerName = 'Topographic';
        
        notification.innerHTML = `<i class="fas fa-check-circle"></i> Switched to ${layerName}`;
        infoBar.appendChild(notification);
        setTimeout(() => notification.remove(), 2000);
    });

    // Add scale bar
    L.control.scale().addTo(map);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>