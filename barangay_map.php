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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        .navbar-modern {
            background: linear-gradient(135deg, #0a2f4d 0%, #123b5e 100%);
            padding: 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .navbar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .pnp-logo {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #c9a959;
        }

        .pnp-logo i {
            font-size: 28px;
            color: #c9a959;
        }

        .title-area h1 {
            font-size: 22px;
            font-weight: 600;
            color: white;
            margin: 0;
            line-height: 1.2;
        }

        .title-area .subtitle {
            font-size: 13px;
            color: #b0c4de;
            margin: 0;
        }

        .title-area .station {
            font-size: 14px;
            color: #c9a959;
            font-weight: 500;
            margin: 2px 0 0;
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 40px;
            border: 1px solid rgba(201, 169, 89, 0.3);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #c9a959;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0a2f4d;
            font-weight: 600;
            font-size: 18px;
        }

        .user-info {
            line-height: 1.3;
        }

        .user-name {
            font-weight: 600;
            color: white;
            font-size: 14px;
        }

        .user-rank {
            font-size: 12px;
            color: #b0c4de;
        }

        .nav-menu {
            background: rgba(0,0,0,0.2);
            padding: 0;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .nav-menu ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 5px;
        }

        .nav-menu li {
            margin: 0;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            color: #e0e0e0;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .nav-menu a i {
            font-size: 16px;
            width: 20px;
        }

        .nav-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-bottom-color: #c9a959;
        }

        .nav-menu a.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-bottom-color: #c9a959;
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

        .page-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
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

        @media (max-width: 768px) {
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
        }
    </style>
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar-modern">
        <div class="navbar-container">
            <div class="navbar-header">
                <div class="logo-area">
                    <div class="pnp-logo">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="title-area">
                        <h1>PNP Biographical Profiling System</h1>
                        <div class="station">MANOLO FORTICH POLICE STATION</div>
                        <div class="subtitle">Bukidnon Police Provincial Office</div>
                    </div>
                </div>
                
                <div class="user-area">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php echo substr($_SESSION['full_name'], 0, 1); ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo $_SESSION['full_name']; ?></div>
                            <div class="user-rank"><?php echo $_SESSION['rank']; ?> • <?php echo $_SESSION['unit']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="nav-menu">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="profile_form.php"><i class="fas fa-plus-circle"></i> New Profile</a></li>
                    <li><a href="profiles.php"><i class="fas fa-list"></i> View Profiles</a></li>
                    <li><a href="barangays.php" class="active"><i class="fas fa-map-marker-alt"></i> Barangays</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="users.php"><i class="fas fa-users-cog"></i> Account</a></li>
                    <?php endif; ?>
                    <li style="margin-left: auto;"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
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
        </div>
    </footer>

    <script>
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