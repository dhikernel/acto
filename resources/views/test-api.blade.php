<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teste API - ACTO</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Teste Isolado da API</h1>

        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <button id="testBtn" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Testar API de Camadas
            </button>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-semibold mb-4">Resultado</h2>
            <div id="result" class="bg-gray-50 p-4 rounded"></div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4">Logs</h2>
            <div id="logs" class="bg-black text-green-400 p-4 rounded font-mono text-sm h-64 overflow-y-auto"></div>
        </div>
    </div>

    <script>
        const logsDiv = document.getElementById('logs');
        const resultDiv = document.getElementById('result');

        function addLog(message, type = 'log') {
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'text-red-400' : 'text-green-400';
            logsDiv.innerHTML += `<div class="${color}">[${timestamp}] ${message}</div>`;
            logsDiv.scrollTop = logsDiv.scrollHeight;
        }

        // Capturar console
        const originalLog = console.log;
        const originalError = console.error;

        console.log = function(...args) {
            addLog(args.join(' '), 'log');
            originalLog.apply(console, args);
        };

        console.error = function(...args) {
            addLog(args.join(' '), 'error');
            originalError.apply(console, args);
        };

        // Função para testar geometria
        function testGeometryType(wkt) {
            console.log('Testando getGeometryType com:', wkt);

            if (!wkt || typeof wkt !== 'string') {
                console.log('Retornando unknown - não é string');
                return 'unknown';
            }

            try {
                const upperWKT = wkt.toUpperCase().trim();
                console.log('upperWKT:', upperWKT);

                if (upperWKT.startsWith('POINT')) return 'Point';
                if (upperWKT.startsWith('LINESTRING')) return 'LineString';
                if (upperWKT.startsWith('POLYGON')) return 'Polygon';

                return 'unknown';
            } catch (error) {
                console.error('Erro ao processar tipo de geometria:', error);
                return 'unknown';
            }
        }

        document.getElementById('testBtn').addEventListener('click', async function() {
            resultDiv.innerHTML = '<div class="text-blue-600">Testando...</div>';

            try {
                console.log('=== INICIANDO TESTE ===');

                // Teste 1: Fetch da API
                console.log('1. Fazendo fetch da API...');
                const response = await fetch('/api/layers');
                console.log('Response status:', response.status);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                // Teste 2: Parse JSON
                console.log('2. Fazendo parse do JSON...');
                const layers = await response.json();
                console.log('Layers recebidas:', layers.length);
                console.log('Primeira layer:', layers[0]);

                // Teste 3: Verificar se é array
                console.log('3. Verificando se é array...');
                if (!Array.isArray(layers)) {
                    throw new Error('Não é um array');
                }

                // Teste 4: Processar cada layer
                console.log('4. Processando layers...');
                let processedCount = 0;
                let errorCount = 0;

                layers.forEach((layer, index) => {
                    try {
                        console.log(`--- Processando layer ${index + 1} ---`);
                        console.log('Layer data:', layer);

                        if (layer && layer.geometry_text) {
                            console.log('Geometry text:', layer.geometry_text);
                            console.log('Tipo da geometry_text:', typeof layer.geometry_text);

                            const geometryType = testGeometryType(layer.geometry_text);
                            console.log('Geometry type resultado:', geometryType);

                            processedCount++;
                        } else {
                            console.log('Layer ignorada - sem geometry_text');
                        }
                    } catch (layerError) {
                        console.error(`Erro na layer ${index + 1}:`, layerError);
                        errorCount++;
                    }
                });

                console.log('=== TESTE CONCLUÍDO ===');
                console.log(`Processadas: ${processedCount}, Erros: ${errorCount}`);

                resultDiv.innerHTML = `
                    <div class="text-green-600 font-semibold">✅ Teste concluído!</div>
                    <div class="mt-2">
                        <p><strong>Total de camadas:</strong> ${layers.length}</p>
                        <p><strong>Processadas com sucesso:</strong> ${processedCount}</p>
                        <p><strong>Erros:</strong> ${errorCount}</p>
                    </div>
                `;

            } catch (error) {
                console.error('ERRO GERAL:', error);
                console.error('Stack trace:', error.stack);

                resultDiv.innerHTML = `
                    <div class="text-red-600 font-semibold">❌ Erro no teste</div>
                    <div class="mt-2">
                        <p><strong>Erro:</strong> ${error.message}</p>
                        <p class="text-sm mt-2">Verifique os logs para mais detalhes</p>
                    </div>
                `;
            }
        });

        console.log('Página de teste carregada');
    </script>
</body>
</html>
