# Sistema de Gestão de dados Georreferenciados - ACTO

Sistema de gestão de dados georreferenciados desenvolvido em Laravel com Filament, integração com mapas ArcGIS e suporte a dados geoespaciais.

## 📋 Pré-requisitos

Antes de começar, certifique-se de ter instalado em sua máquina:

- [Docker](https://docs.docker.com/get-docker/) (versão 20.10 ou superior)
- [Docker Compose](https://docs.docker.com/compose/install/) (versão 1.29 ou superior)
- [Git](https://git-scm.com/)

## 🚀 Instalação e Configuração

### 1. Clone o Repositório

```bash
git clone git@github.com:dhikernel/acto.git
```

```bash
cd acto
```

### 2. Configuração do Ambiente

#### 2.1. Arquivo de Ambiente
Copie o arquivo de exemplo e configure as variáveis de ambiente:

```bash
cp .env.example .env
```

#### 2.2. Configure as Variáveis de Ambiente
Edite o arquivo `.env` com as seguintes configurações:

```env
# Aplicação
APP_NAME="Sistema ACTO"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=America/Sao_Paulo
APP_URL=http://localhost:8989

# Banco de Dados PostgreSQL (Docker)
DB_CONNECTION=pgsql
DB_HOST=setup_postgres
DB_PORT=5432
DB_DATABASE=setup
DB_USERNAME=diego
DB_PASSWORD=12345678

# Cache e Sessão
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

# Mail (opcional - para desenvolvimento)
MAIL_MAILER=log
```

### 3. Inicialização com Docker

#### 3.1. Construir e Iniciar os Containers
```bash
docker compose up -d --build
```

#### 3.2. Verificar Status dos Containers
```bash
docker compose ps
```

Você deve ver 3 containers rodando:
- `setup_postgres` (PostgreSQL com PostGIS)
- `setup_site` (PHP-FPM)
- `setup_nginx` (Nginx)

### 4. Configuração da Aplicação Laravel

#### 4.1. Instalar Dependências PHP
```bash
docker compose exec setup_site bash
```

```bash
composer install
```

```bash
php artisan key:generate
```

#### 4.2. Configurar Banco de Dados
```bash
php artisan migrate
```

```bash
php artisan db:seed
```

#### 4.3. Instalar Dependências Frontend
```bash
npm install
```

```bash
npm run build
```

Para desenvolvimento (com watch):
```bash
npm run dev
```

#### 4.4. Configurar Permissões
```bash
chmod -R 775 storage bootstrap/cache
```

```bash
chown -R www-data:www-data storage bootstrap/cache
```

#### 4.5. Sair do Container
```bash
exit
```

### 5. Configuração do Filament (Admin Panel)

#### 5.1. Criar Usuário Administrador
```bash
docker compose exec setup_site bash
```

```bash
php artisan make:filament-user
```

Siga as instruções para criar um usuário administrador.

## 🌐 Acessando a Aplicação

Após a instalação completa, você pode acessar:

- **Aplicação Principal**: http://localhost:8989
- **Painel Administrativo (Filament)**: http://localhost:8989/admin
- **Banco de Dados PostgreSQL**: 
  - Host: localhost
  - Porta: 5432
  - Database: setup
  - Usuário: diego
  - Senha: 12345678

## 🛠️ Comandos Úteis para Desenvolvimento

### Gerenciamento de Containers

Iniciar containers:
```bash
docker compose up -d
```

Parar containers:
```bash
docker compose down
```

Reiniciar containers:
```bash
docker compose restart
```

Ver logs:
```bash
docker compose logs -f
```

Ver logs de um serviço específico:
```bash
docker compose logs -f setup_site
```

### Comandos Laravel (dentro do container)

Entrar no container:
```bash
docker compose exec setup_site bash
```

Limpar cache:
```bash
php artisan cache:clear
```

```bash
php artisan config:clear
```

```bash
php artisan route:clear
```

```bash
php artisan view:clear
```

Executar migrações:
```bash
php artisan migrate
```

Rollback migrações:
```bash
php artisan migrate:rollback
```

Executar seeders:
```bash
php artisan db:seed
```

Executar testes:
```bash
php artisan test
```

### Desenvolvimento Frontend

Modo desenvolvimento (com hot reload):
```bash
npm run dev
```

Build para produção:
```bash
npm run build
```

Instalar nova dependência:
```bash
npm install <nome-do-pacote>
```

## 📁 Estrutura do Projeto

```
acto/
├── app/                    # Código da aplicação Laravel
│   ├── Filament/          # Recursos do Filament Admin
│   ├── Http/              # Controllers, Middleware, Requests
│   ├── Models/            # Models Eloquent
│   └── Services/          # Serviços da aplicação
├── config/                # Arquivos de configuração
├── database/              # Migrações, seeders, factories
├── docker/                # Configurações Docker
│   ├── nginx/            # Configuração Nginx
│   ├── php/              # Dockerfile PHP
│   └── postgres/         # Scripts PostgreSQL
├── public/               # Assets públicos
├── resources/            # Views, assets, lang
├── routes/               # Definição de rotas
├── storage/              # Logs, cache, uploads
└── tests/                # Testes automatizados
```

## 🗺️ Funcionalidades Principais

- **Gestão de dados georreferenciados**: CRUD completo de gestão de dados geoespaciais
- **Mapas Interativos**: Integração com ArcGIS para visualização geográfica
- **Upload GeoJSON**: Suporte para importação de arquivos GeoJSON
- **Painel Administrativo**: Interface administrativa completa com Filament
- **API RESTful**: Endpoints para integração com outros sistemas
- **Banco Geoespacial**: PostgreSQL com extensão PostGIS

## 🔧 Solução de Problemas

### Container não inicia

Verificar logs do container:
```bash
docker compose logs setup_site
```

Reconstruir containers:
```bash
docker compose down
```

```bash
docker compose up -d --build --force-recreate
```

### Erro de permissões

Dentro do container:
```bash
chmod -R 775 storage bootstrap/cache
```

```bash
chown -R www-data:www-data storage bootstrap/cache
```

### Erro de conexão com banco

Verificar se o PostgreSQL está rodando:
```bash
docker compose ps setup_postgres
```

Verificar logs do PostgreSQL:
```bash
docker compose logs setup_postgres
```

Testar conexão:
```bash
docker compose exec setup_postgres psql -U diego -d setup
```

### Assets não carregam

Recompilar assets:
```bash
npm run build
```

Limpar cache do Laravel:
```bash
php artisan cache:clear
```

```bash
php artisan config:clear
```

## 📚 Documentação Adicional

- [Documentação do Laravel](https://laravel.com/docs)
- [Documentação do Filament](https://filamentphp.com/docs)
- [ArcGIS API for JavaScript](https://developers.arcgis.com/javascript/)
- [PostGIS Documentation](https://postgis.net/documentation/)

## 🤝 Contribuição

1. Faça um fork do projeto

2. Crie uma branch para sua feature:
```bash
git checkout -b feature/AmazingFeature
```

3. Commit suas mudanças:
```bash
git commit -m 'Add some AmazingFeature'
```

4. Push para a branch:
```bash
git push origin feature/AmazingFeature
```

5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## 📞 Suporte

Para suporte técnico ou dúvidas sobre o projeto, entre em contato através dos canais oficiais da equipe de desenvolvimento.
