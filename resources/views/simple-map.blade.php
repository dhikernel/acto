<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mapa Simples - ACTO</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        #map {
            height: 100vh;
            width: 100%;
        }
        
        .layers-panel {
            position: absolute;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 300px;
            z-index: 1000;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .layer-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
        }
        
        .layer-checkbox {
            margin-right: 10px;
        }
        
        .layer-name {
            font-weight: 500;
            color: #333;
            flex: 1;
        }
        
        .layer-type {
            font-size: 12px;
            color: #666;
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white p-6 rounded-lg shadow-lg z-50">
        <div class="flex items-center">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mr-3"></div>
            <span>Carregando mapa e camadas...</span>
        </div>
    </div>
    
    <!-- Layers Panel -->
    <div id="layersPanel" class="layers-panel" style="display: none;">
        <h3 class="text-lg font-semibold mb-3">Camadas Disponíveis</h3>
        <div id="layersList">
            <!-- Layers will be populated here -->
        </div>
    </div>
    
    <!-- Map Container -->
    <div id="map"></div>
    
    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Initialize the map
        const map = L.map('map').setView([-23.5505, -46.6333], 10);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Store layers
        const layersData = [];
        const mapLayers = new Map();
        
        // Function to parse WKT and create Leaflet layer
        function createLayerFromWKT(wkt, layerInfo) {
            if (!wkt) return null;
            
            const upperWKT = wkt.toUpperCase().trim();
            
            try {
                if (upperWKT.startsWith('POINT')) {
                    const coords = wkt.match(/POINT\s*\(\s*([^)]+)\)/i);
                    if (coords) {
                        const [lng, lat] = coords[1].trim().split(/\s+/).map(Number);
                        return L.marker([lat, lng])
                            .bindPopup(`
                                <h4>${layerInfo.name}</h4>
                                <p><strong>Tipo:</strong> Point</p>
                                <p><strong>ID:</strong> ${layerInfo.id}</p>
                                <p><strong>Coordenadas:</strong> ${lat.toFixed(4)}, ${lng.toFixed(4)}</p>
                            `);
                    }
                } else if (upperWKT.startsWith('LINESTRING')) {
                    const coords = wkt.match(/LINESTRING\s*\(\s*([^)]+)\)/i);
                    if (coords) {
                        const points = coords[1].split(',').map(coord => {
                            const [lng, lat] = coord.trim().split(/\s+/).map(Number);
                            return [lat, lng];
                        });
                        return L.polyline(points, {color: 'blue', weight: 3})
                            .bindPopup(`
                                <h4>${layerInfo.name}</h4>
                                <p><strong>Tipo:</strong> LineString</p>
                                <p><strong>ID:</strong> ${layerInfo.id}</p>
                                <p><strong>Pontos:</strong> ${points.length}</p>
                            `);
                    }
                } else if (upperWKT.startsWith('POLYGON')) {
                    const coords = wkt.match(/POLYGON\s*\(\s*\(([^)]+)\)\s*\)/i);
                    if (coords) {
                        const points = coords[1].split(',').map(coord => {
                            const [lng, lat] = coord.trim().split(/\s+/).map(Number);
                            return [lat, lng];
                        });
                        return L.polygon(points, {color: 'red', fillColor: 'red', fillOpacity: 0.3})
                            .bindPopup(`
                                <h4>${layerInfo.name}</h4>
                                <p><strong>Tipo:</strong> Polygon</p>
                                <p><strong>ID:</strong> ${layerInfo.id}</p>
                                <p><strong>Vértices:</strong> ${points.length}</p>
                            `);
                    }
                }
            } catch (error) {
                console.error('Erro ao processar WKT:', error);
            }
            
            return null;
        }
        
        // Function to get geometry type
        function getGeometryType(wkt) {
            if (!wkt) return 'unknown';
            const upperWKT = wkt.toUpperCase().trim();
            
            if (upperWKT.startsWith('POINT')) return 'Point';
            if (upperWKT.startsWith('LINESTRING')) return 'LineString';
            if (upperWKT.startsWith('POLYGON')) return 'Polygon';
            
            return 'unknown';
        }
        
        // Function to load layers from API
        async function loadLayers() {
            try {
                console.log('Carregando camadas...');
                
                const response = await fetch('/api/layers');
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const layers = await response.json();
                console.log('Camadas recebidas:', layers.length);
                
                layers.forEach(layer => {
                    if (layer.geometry_text) {
                        console.log(`Processando camada: ${layer.name} (${layer.geometry_text.substring(0, 50)}...)`);
                        
                        const leafletLayer = createLayerFromWKT(layer.geometry_text, layer);
                        
                        if (leafletLayer) {
                            leafletLayer.addTo(map);
                            mapLayers.set(layer.id, leafletLayer);
                            
                            layersData.push({
                                id: layer.id,
                                name: layer.name || `Camada ${layer.id}`,
                                type: getGeometryType(layer.geometry_text),
                                layer: leafletLayer,
                                visible: true
                            });
                        }
                    }
                });
                
                // Update layers panel
                updateLayersPanel();
                
                // Show layers panel
                document.getElementById('layersPanel').style.display = 'block';
                
                // Hide loading indicator
                document.getElementById('loadingIndicator').style.display = 'none';
                
                console.log(`✅ Carregadas ${layersData.length} camadas com sucesso`);
                
                // Fit map to show all layers
                if (layersData.length > 0) {
                    const group = new L.featureGroup(Array.from(mapLayers.values()));
                    map.fitBounds(group.getBounds().pad(0.1));
                }
                
            } catch (error) {
                console.error('❌ Erro ao carregar camadas:', error);
                document.getElementById('loadingIndicator').innerHTML = 
                    `<div class="text-red-600">
                        <p class="font-semibold">Erro ao carregar camadas</p>
                        <p class="text-sm mt-2">${error.message}</p>
                        <button onclick="location.reload()" class="mt-3 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            Tentar Novamente
                        </button>
                    </div>`;
            }
        }
        
        // Function to update layers panel
        function updateLayersPanel() {
            const layersList = document.getElementById('layersList');
            layersList.innerHTML = '';
            
            layersData.forEach(layerData => {
                const layerItem = document.createElement('div');
                layerItem.className = 'layer-item';
                
                layerItem.innerHTML = `
                    <input type="checkbox" class="layer-checkbox" 
                           id="layer-${layerData.id}" 
                           ${layerData.visible ? 'checked' : ''}>
                    <label for="layer-${layerData.id}" class="layer-name">${layerData.name}</label>
                    <span class="layer-type">${layerData.type}</span>
                `;
                
                // Add event listener for visibility toggle
                const checkbox = layerItem.querySelector('.layer-checkbox');
                checkbox.addEventListener('change', function() {
                    layerData.visible = this.checked;
                    
                    if (this.checked) {
                        layerData.layer.addTo(map);
                    } else {
                        map.removeLayer(layerData.layer);
                    }
                });
                
                layersList.appendChild(layerItem);
            });
        }
        
        // Load layers when map is ready
        map.whenReady(function() {
            console.log('Mapa Leaflet carregado');
            loadLayers();
        });
        
        console.log('Inicializando mapa simples...');
    </script>
</body>
</html>
