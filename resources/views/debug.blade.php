<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Debug - Teste de Camadas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Debug - Teste de Carregamento de Camadas</h1>

        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-semibold mb-4">Status da API</h2>
            <button id="testApi" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Testar API
            </button>
            <div id="apiResult" class="mt-4 p-4 bg-gray-50 rounded"></div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-semibold mb-4">Teste ArcGIS SDK</h2>
            <button id="testArcGIS" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                Testar ArcGIS
            </button>
            <div id="arcgisResult" class="mt-4 p-4 bg-gray-50 rounded"></div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4">Logs do Console</h2>
            <div id="consoleLogs" class="bg-black text-green-400 p-4 rounded font-mono text-sm h-64 overflow-y-auto"></div>
        </div>
    </div>

    <script>
        // Capturar logs do console
        const originalLog = console.log;
        const originalError = console.error;
        const logsDiv = document.getElementById('consoleLogs');

        function addLog(message, type = 'log') {
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'text-red-400' : 'text-green-400';
            logsDiv.innerHTML += `<div class="${color}">[${timestamp}] ${message}</div>`;
            logsDiv.scrollTop = logsDiv.scrollHeight;
        }

        console.log = function(...args) {
            addLog(args.join(' '), 'log');
            originalLog.apply(console, args);
        };

        console.error = function(...args) {
            addLog(args.join(' '), 'error');
            originalError.apply(console, args);
        };

        // Teste da API
        document.getElementById('testApi').addEventListener('click', async function() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '<div class="text-blue-600">Testando API...</div>';

            try {
                console.log('Iniciando teste da API...');
                const response = await fetch('/api/layers');
                console.log('Response status:', response.status);
                console.log('Response headers:', [...response.headers.entries()]);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                console.log('Dados recebidos:', data);

                resultDiv.innerHTML = `
                    <div class="text-green-600 font-semibold">✅ API funcionando!</div>
                    <div class="mt-2">
                        <strong>Camadas encontradas:</strong> ${data.length}<br>
                        <strong>Primeira camada:</strong> ${data[0]?.name || 'N/A'}<br>
                        <strong>Geometria:</strong> ${data[0]?.geometry_text || 'N/A'}
                    </div>
                `;

            } catch (error) {
                console.error('Erro na API:', error);
                resultDiv.innerHTML = `
                    <div class="text-red-600 font-semibold">❌ Erro na API</div>
                    <div class="mt-2 text-sm">${error.message}</div>
                `;
            }
        });

        // Teste do ArcGIS
        document.getElementById('testArcGIS').addEventListener('click', function() {
            const resultDiv = document.getElementById('arcgisResult');
            resultDiv.innerHTML = '<div class="text-blue-600">Testando ArcGIS SDK...</div>';

            // Carregar ArcGIS SDK
            const script = document.createElement('script');
            script.src = 'https://js.arcgis.com/4.31/';
            script.onload = function() {
                console.log('ArcGIS SDK carregado');

                require([
                    "esri/Map"
                    , "esri/views/MapView"
                ], function(Map, MapView) {
                    console.log('Módulos ArcGIS carregados com sucesso');

                    resultDiv.innerHTML = `
                        <div class="text-green-600 font-semibold">✅ ArcGIS SDK funcionando!</div>
                        <div class="mt-2">Módulos Map e MapView carregados com sucesso.</div>
                    `;

                }, function(error) {
                    console.error('Erro ao carregar módulos ArcGIS:', error);
                    resultDiv.innerHTML = `
                        <div class="text-red-600 font-semibold">❌ Erro nos módulos ArcGIS</div>
                        <div class="mt-2 text-sm">${error.message}</div>
                    `;
                });
            };

            script.onerror = function() {
                console.error('Erro ao carregar ArcGIS SDK');
                resultDiv.innerHTML = `
                    <div class="text-red-600 font-semibold">❌ Erro ao carregar ArcGIS SDK</div>
                    <div class="mt-2 text-sm">Falha no carregamento do script</div>
                `;
            };

            document.head.appendChild(script);
        });

        console.log('Página de debug carregada');

    </script>
</body>
</html>
