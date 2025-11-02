<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ACTO') }} - Mapa Interativo</title>

    <!-- ArcGIS Maps SDK CSS -->
    <link rel="stylesheet" href="https://js.arcgis.com/4.31/esri/themes/light/main.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        #mapContainer {
            height: 100vh;
            width: 100%;
        }

        .map-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
        }

        .layer-type {
            font-size: 12px;
            color: #666;
            margin-left: auto;
        }
    </style>
</head>
<body>
    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="map-loading">
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
    <div id="mapContainer"></div>

    <!-- ArcGIS Maps SDK JavaScript -->
    <script>
        // Verificar se o ArcGIS SDK carregou corretamente
        function checkArcGISSDK() {
            if (typeof require === 'undefined') {
                console.error('ArcGIS SDK não carregou corretamente');
                document.getElementById('loadingIndicator').innerHTML =
                    `<div class="text-red-600">
                        <p class="font-semibold">Erro ao carregar ArcGIS SDK</p>
                        <p class="text-sm mt-2">Verifique sua conexão com a internet</p>
                        <div class="mt-3">
                            <a href="/simple" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 mr-2">
                                Usar Mapa Simples
                            </a>
                            <button onclick="location.reload()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                                Tentar Novamente
                            </button>
                        </div>
                    </div>`;
                return false;
            }
            return true;
        }

        // Carregar ArcGIS SDK
        const arcgisScript = document.createElement('script');
        arcgisScript.src = 'https://js.arcgis.com/4.31/';
        arcgisScript.onload = function() {
            console.log('ArcGIS SDK carregado');
            if (checkArcGISSDK()) {
                initializeMap();
            }
        };
        arcgisScript.onerror = function() {
            console.error('Falha ao carregar ArcGIS SDK');
            document.getElementById('loadingIndicator').innerHTML =
                `<div class="text-red-600">
                    <p class="font-semibold">Falha ao carregar ArcGIS SDK</p>
                    <p class="text-sm mt-2">Problema de conectividade ou CDN indisponível</p>
                    <div class="mt-3">
                        <a href="/simple" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 mr-2">
                            Usar Mapa Simples (Leaflet)
                        </a>
                        <button onclick="location.reload()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                            Tentar Novamente
                        </button>
                    </div>
                </div>`;
        };
        document.head.appendChild(arcgisScript);

        function initializeMap() {
        require([
            "esri/Map",
            "esri/views/MapView",
            "esri/layers/FeatureLayer",
            "esri/layers/GraphicsLayer",
            "esri/Graphic",
            "esri/geometry/Point",
            "esri/geometry/Polyline",
            "esri/geometry/Polygon",
            "esri/symbols/SimpleMarkerSymbol",
            "esri/symbols/SimpleLineSymbol",
            "esri/symbols/SimpleFillSymbol",
            "esri/widgets/LayerList",
            "esri/widgets/Legend",
            "esri/widgets/ScaleBar",
            "esri/widgets/Zoom"
        ], function(
            Map, MapView, FeatureLayer, GraphicsLayer, Graphic,
            Point, Polyline, Polygon,
            SimpleMarkerSymbol, SimpleLineSymbol, SimpleFillSymbol,
            LayerList, Legend, ScaleBar, Zoom
        ) {

            // Create the map
            const map = new Map({
                basemap: "streets-navigation-vector"
            });

            // Create the map view
            const view = new MapView({
                container: "mapContainer",
                map: map,
                center: [-46.6333, -23.5505], // São Paulo coordinates
                zoom: 10
            });

            // Store layers for management
            const layersData = [];
            const graphicsLayers = new Map();

            // Function to create symbol based on geometry type
            function createSymbol(geometryType) {
                switch (geometryType.toLowerCase()) {
                    case 'point':
                    case 'multipoint':
                        return new SimpleMarkerSymbol({
                            color: [226, 119, 40],
                            outline: {
                                color: [255, 255, 255],
                                width: 2
                            },
                            size: 8
                        });
                    case 'linestring':
                    case 'multilinestring':
                        return new SimpleLineSymbol({
                            color: [226, 119, 40],
                            width: 3
                        });
                    case 'polygon':
                    case 'multipolygon':
                        return new SimpleFillSymbol({
                            color: [226, 119, 40, 0.3],
                            outline: {
                                color: [226, 119, 40],
                                width: 2
                            }
                        });
                    default:
                        return new SimpleMarkerSymbol({
                            color: [226, 119, 40],
                            size: 8
                        });
                }
            }

            // Function to convert WKT geometry to ArcGIS geometry
            function parseWKTGeometry(wkt) {
                if (!wkt || typeof wkt !== 'string') return null;

                try {
                    // Simple WKT parser for basic geometries
                    const upperWKT = wkt.toUpperCase().trim();

                    if (upperWKT.startsWith('POINT')) {
                        const coords = wkt.match(/POINT\s*\(\s*([^)]+)\)/i);
                        if (coords) {
                            const [x, y] = coords[1].split(/\s+/).map(Number);
                            return new Point({
                                longitude: x,
                                latitude: y,
                                spatialReference: { wkid: 4326 }
                            });
                        }
                    } else if (upperWKT.startsWith('LINESTRING')) {
                        const coords = wkt.match(/LINESTRING\s*\(\s*([^)]+)\)/i);
                        if (coords) {
                            const points = coords[1].split(',').map(coord => {
                                const [x, y] = coord.trim().split(/\s+/).map(Number);
                                return [x, y];
                            });
                            return new Polyline({
                                paths: [points],
                                spatialReference: { wkid: 4326 }
                            });
                        }
                    } else if (upperWKT.startsWith('POLYGON')) {
                        const coords = wkt.match(/POLYGON\s*\(\s*\(([^)]+)\)\s*\)/i);
                        if (coords) {
                            const points = coords[1].split(',').map(coord => {
                                const [x, y] = coord.trim().split(/\s+/).map(Number);
                                return [x, y];
                            });
                            return new Polygon({
                                rings: [points],
                                spatialReference: { wkid: 4326 }
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error parsing WKT:', error);
                }

                return null;
            }

            // Function to get geometry type from WKT
            function getGeometryType(wkt) {
                if (!wkt || typeof wkt !== 'string') return 'unknown';

                try {
                    const upperWKT = wkt.toUpperCase().trim();

                    if (upperWKT.startsWith('POINT')) return 'Point';
                    if (upperWKT.startsWith('LINESTRING')) return 'LineString';
                    if (upperWKT.startsWith('POLYGON')) return 'Polygon';
                    if (upperWKT.startsWith('MULTIPOINT')) return 'MultiPoint';
                    if (upperWKT.startsWith('MULTILINESTRING')) return 'MultiLineString';
                    if (upperWKT.startsWith('MULTIPOLYGON')) return 'MultiPolygon';

                    return 'unknown';
                } catch (error) {
                    console.error('Erro ao processar tipo de geometria:', error, 'WKT:', wkt);
                    return 'unknown';
                }
            }

            // Function to load layers from database
            async function loadLayers() {
                try {
                    console.log('Iniciando carregamento das camadas...');
                    const response = await fetch('/api/layers');
                    console.log('Response status:', response.status);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const layers = await response.json();
                    console.log('Camadas carregadas:', layers.length);
                    console.log('Dados das camadas:', layers);

                    // Verificar se layers é um array
                    if (!Array.isArray(layers)) {
                        throw new Error('API não retornou um array de camadas');
                    }

                    layers.forEach((layer, index) => {
                        try {
                            console.log(`Processando camada ${index + 1}:`, layer);

                            if (layer && layer.geometry_text && typeof layer.geometry_text === 'string') {
                                console.log(`Camada ${index + 1} - Geometria:`, layer.geometry_text);

                                // Create graphics layer for this layer
                                const graphicsLayer = new GraphicsLayer({
                                    title: layer.name || `Camada ${layer.id}`,
                                    visible: true
                                });

                                // Parse geometry and create graphic
                                console.log(`Camada ${index + 1} - Parseando geometria...`);
                                const geometry = parseWKTGeometry(layer.geometry_text);

                                if (geometry) {
                                    console.log(`Camada ${index + 1} - Geometria parseada com sucesso`);
                                    const geometryType = getGeometryType(layer.geometry_text);
                                    console.log(`Camada ${index + 1} - Tipo:`, geometryType);

                                    const symbol = createSymbol(geometryType);
                                    console.log(`Camada ${index + 1} - Símbolo criado`);

                                    const graphic = new Graphic({
                                        geometry: geometry,
                                        symbol: symbol,
                                        attributes: {
                                            id: layer.id,
                                            name: layer.name,
                                            type: geometryType
                                        },
                                        popupTemplate: {
                                            title: layer.name || `Camada ${layer.id}`,
                                        content: `
                                            <div>
                                                <p><strong>ID:</strong> ${layer.id}</p>
                                                <p><strong>Tipo:</strong> ${geometryType}</p>
                                                <p><strong>Criado em:</strong> ${layer.created_at}</p>
                                            </div>
                                        `
                                        }
                                    });

                                    console.log(`Camada ${index + 1} - Graphic criado`);
                                    graphicsLayer.add(graphic);
                                    console.log(`Camada ${index + 1} - Graphic adicionado ao layer`);
                                } else {
                                    console.warn(`Camada ${index + 1} - Falha ao parsear geometria`);
                                }

                                // Add layer to map
                                map.add(graphicsLayer);
                                graphicsLayers.set(layer.id, graphicsLayer);
                                console.log(`Camada ${index + 1} - Adicionada ao mapa`);

                                // Store layer data
                                layersData.push({
                                    id: layer.id,
                                    name: layer.name || `Camada ${layer.id}`,
                                    type: getGeometryType(layer.geometry_text),
                                    layer: graphicsLayer
                                });
                                console.log(`Camada ${index + 1} - Dados armazenados`);

                            } else {
                                console.warn(`Camada ${index + 1} ignorada:`, {
                                    id: layer?.id,
                                    name: layer?.name,
                                    hasGeometry: !!layer?.geometry_text,
                                    geometryType: typeof layer?.geometry_text
                                });
                            }
                        } catch (layerError) {
                            console.error(`Erro ao processar camada ${index + 1}:`, layerError);
                            console.error('Dados da camada com erro:', layer);
                        }
                    });

                    // Update layers panel
                    updateLayersPanel();

                    // Show layers panel
                    document.getElementById('layersPanel').style.display = 'block';

                    // Hide loading indicator
                    document.getElementById('loadingIndicator').style.display = 'none';

                    console.log(`Carregadas ${layers.length} camadas`);

                } catch (error) {
                    console.error('Erro ao carregar camadas:', error);
                    console.error('Error details:', error.message);
                    document.getElementById('loadingIndicator').innerHTML =
                        `<div class="text-red-600">
                            <p>Erro ao carregar camadas</p>
                            <p class="text-sm mt-2">Detalhes: ${error.message}</p>
                            <p class="text-xs mt-1">Verifique o console para mais informações</p>
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
                               ${layerData.layer.visible ? 'checked' : ''}>
                        <label for="layer-${layerData.id}" class="layer-name">${layerData.name}</label>
                        <span class="layer-type">${layerData.type}</span>
                    `;

                    // Add event listener for visibility toggle
                    const checkbox = layerItem.querySelector('.layer-checkbox');
                    checkbox.addEventListener('change', function() {
                        layerData.layer.visible = this.checked;
                    });

                    layersList.appendChild(layerItem);
                });
            }

            // Add widgets to the view
            view.when(() => {
                // Add zoom widget
                const zoom = new Zoom({
                    view: view
                });
                view.ui.add(zoom, "top-left");

                // Add scale bar
                const scaleBar = new ScaleBar({
                    view: view,
                    unit: "metric"
                });
                view.ui.add(scaleBar, "bottom-left");

                // Load layers after map is ready
                loadLayers();
            });

            // Handle view errors
            view.when(
                () => {
                    console.log("Mapa carregado com sucesso");
                },
                (error) => {
                    console.error("Erro ao carregar o mapa:", error);
                    document.getElementById('loadingIndicator').innerHTML =
                        '<div class="text-red-600">Erro ao carregar o mapa</div>';
                }
            );
        });
        } // Fim da função initializeMap
    </script>
</body>
</html>
