/**
 * Utilitários para o mapa ArcGIS
 */

export class MapUtils {
    /**
     * Converte geometria WKT para formato ArcGIS
     */
    static parseWKTGeometry(wkt, Point, Polyline, Polygon) {
        if (!wkt) return null;
        
        try {
            const upperWKT = wkt.toUpperCase().trim();
            
            if (upperWKT.startsWith('POINT')) {
                return this.parsePoint(wkt, Point);
            } else if (upperWKT.startsWith('LINESTRING')) {
                return this.parseLineString(wkt, Polyline);
            } else if (upperWKT.startsWith('POLYGON')) {
                return this.parsePolygon(wkt, Polygon);
            } else if (upperWKT.startsWith('MULTIPOINT')) {
                return this.parseMultiPoint(wkt, Point);
            } else if (upperWKT.startsWith('MULTILINESTRING')) {
                return this.parseMultiLineString(wkt, Polyline);
            } else if (upperWKT.startsWith('MULTIPOLYGON')) {
                return this.parseMultiPolygon(wkt, Polygon);
            }
        } catch (error) {
            console.error('Erro ao processar WKT:', error);
        }
        
        return null;
    }
    
    /**
     * Parse POINT WKT
     */
    static parsePoint(wkt, Point) {
        const coords = wkt.match(/POINT\s*\(\s*([^)]+)\)/i);
        if (coords) {
            const [x, y] = coords[1].trim().split(/\s+/).map(Number);
            return new Point({
                longitude: x,
                latitude: y,
                spatialReference: { wkid: 4326 }
            });
        }
        return null;
    }
    
    /**
     * Parse LINESTRING WKT
     */
    static parseLineString(wkt, Polyline) {
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
        return null;
    }
    
    /**
     * Parse POLYGON WKT
     */
    static parsePolygon(wkt, Polygon) {
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
        return null;
    }
    
    /**
     * Obtém o tipo de geometria do WKT
     */
    static getGeometryType(wkt) {
        if (!wkt) return 'unknown';
        const upperWKT = wkt.toUpperCase().trim();
        
        if (upperWKT.startsWith('POINT')) return 'Point';
        if (upperWKT.startsWith('LINESTRING')) return 'LineString';
        if (upperWKT.startsWith('POLYGON')) return 'Polygon';
        if (upperWKT.startsWith('MULTIPOINT')) return 'MultiPoint';
        if (upperWKT.startsWith('MULTILINESTRING')) return 'MultiLineString';
        if (upperWKT.startsWith('MULTIPOLYGON')) return 'MultiPolygon';
        
        return 'unknown';
    }
    
    /**
     * Cria símbolos baseados no tipo de geometria
     */
    static createSymbol(geometryType, SimpleMarkerSymbol, SimpleLineSymbol, SimpleFillSymbol) {
        const colors = {
            primary: [226, 119, 40],
            secondary: [255, 255, 255],
            fill: [226, 119, 40, 0.3]
        };
        
        switch (geometryType.toLowerCase()) {
            case 'point':
            case 'multipoint':
                return new SimpleMarkerSymbol({
                    color: colors.primary,
                    outline: {
                        color: colors.secondary,
                        width: 2
                    },
                    size: 10
                });
                
            case 'linestring':
            case 'multilinestring':
                return new SimpleLineSymbol({
                    color: colors.primary,
                    width: 3,
                    style: "solid"
                });
                
            case 'polygon':
            case 'multipolygon':
                return new SimpleFillSymbol({
                    color: colors.fill,
                    outline: {
                        color: colors.primary,
                        width: 2
                    }
                });
                
            default:
                return new SimpleMarkerSymbol({
                    color: colors.primary,
                    size: 8
                });
        }
    }
    
    /**
     * Formata data para exibição
     */
    static formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('pt-BR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    /**
     * Cria popup template para uma camada
     */
    static createPopupTemplate(layer) {
        return {
            title: layer.name || `Camada ${layer.id}`,
            content: `
                <div class="layer-popup">
                    <div class="popup-section">
                        <h4>Informações da Camada</h4>
                        <p><strong>ID:</strong> ${layer.id}</p>
                        <p><strong>Nome:</strong> ${layer.name || 'Sem nome'}</p>
                        <p><strong>Tipo de Geometria:</strong> ${this.getGeometryType(layer.geometry_text)}</p>
                    </div>
                    <div class="popup-section">
                        <h4>Datas</h4>
                        <p><strong>Criado em:</strong> ${this.formatDate(layer.created_at)}</p>
                        ${layer.updated_at !== layer.created_at ? 
                            `<p><strong>Atualizado em:</strong> ${this.formatDate(layer.updated_at)}</p>` : 
                            ''
                        }
                    </div>
                </div>
                <style>
                    .layer-popup { font-family: Arial, sans-serif; }
                    .popup-section { margin-bottom: 15px; }
                    .popup-section h4 { 
                        margin: 0 0 8px 0; 
                        color: #333; 
                        border-bottom: 1px solid #eee; 
                        padding-bottom: 4px; 
                    }
                    .popup-section p { 
                        margin: 4px 0; 
                        font-size: 14px; 
                    }
                </style>
            `
        };
    }
}

/**
 * Gerenciador de camadas
 */
export class LayerManager {
    constructor(map) {
        this.map = map;
        this.layers = new Map();
        this.layersData = [];
    }
    
    /**
     * Adiciona uma camada ao mapa
     */
    addLayer(layerData, graphicsLayer) {
        this.layers.set(layerData.id, graphicsLayer);
        this.layersData.push(layerData);
        this.map.add(graphicsLayer);
    }
    
    /**
     * Remove uma camada do mapa
     */
    removeLayer(layerId) {
        const layer = this.layers.get(layerId);
        if (layer) {
            this.map.remove(layer);
            this.layers.delete(layerId);
            this.layersData = this.layersData.filter(l => l.id !== layerId);
        }
    }
    
    /**
     * Alterna visibilidade de uma camada
     */
    toggleLayerVisibility(layerId, visible) {
        const layer = this.layers.get(layerId);
        if (layer) {
            layer.visible = visible;
        }
    }
    
    /**
     * Obtém todas as camadas
     */
    getAllLayers() {
        return this.layersData;
    }
    
    /**
     * Obtém uma camada específica
     */
    getLayer(layerId) {
        return this.layers.get(layerId);
    }
}
