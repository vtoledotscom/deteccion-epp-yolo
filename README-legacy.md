# Detección EPP Web

Interfaz web para consulta de eventos de detección de Elementos de Protección Personal (EPP), construida sobre Laravel 13 y Livewire 4.

## Objetivo

Esta aplicación permite:

- visualizar eventos detectados por el MVP de detección EPP
- revisar el detalle de cada evento
- visualizar evidencias asociadas
- consultar eventos abiertos
- generar reportes
- exportar resultados en CSV y PDF

## Stack

- PHP 8.4
- Laravel 13
- Livewire 4
- MySQL / MariaDB
- Vite
- DomPDF

## Alcance actual (V1)

- Dashboard con métricas básicas
- Lista de eventos con filtros
- Detalle de evento
- Visualización de evidencias
- Eventos abiertos
- Reportes
- Exportación CSV
- Exportación PDF

## Requisitos

- PHP 8.4 o superior
- Composer
- Node.js + npm
- MySQL o MariaDB
- Proyecto Python del MVP EPP disponible localmente
- Acceso a la base de datos `deteccion_epp`

## Instalación

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate

Configuración

Editar el archivo .env con:

conexión a base de datos
rutas locales del proyecto MVP Python
ruta de evidencias
rutas de cameras.json y scenarios.json

Variables relevantes:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=deteccion_epp
DB_USERNAME=root
DB_PASSWORD=

EPP_PROJECT_BASE_PATH=/ruta/completa/mvp-epp-yolo
EPP_EVIDENCE_BASE_PATH=/ruta/completa/mvp-epp-yolo/evidence
EPP_CAMERAS_JSON_PATH=/ruta/completa/mvp-epp-yolo/config/cameras.json
EPP_SCENARIOS_JSON_PATH=/ruta/completa/mvp-epp-yolo/config/scenarios.json

Levantar el proyecto

En una terminal: php artisan serve
En otra terminal: npm run dev
Luego abrir: http://127.0.0.1:8000
Estructura funcional principal
/dashboard
/events
/events/open
/reports
Notas importantes
No subir .env
No subir evidencias ni archivos exportados
No subir vendor/ ni node_modules/
Las evidencias se sirven desde disco local mediante Laravel
Cámaras y escenarios se leen desde archivos JSON del MVP Python
Seguridad

Antes de publicar el repositorio:

revisar que no existan credenciales hardcodeadas
verificar que .env no esté trackeado por Git
verificar que la carpeta evidence/ no esté versionada
verificar que no existan exports .csv, .pdf o .zip dentro del repo
verificar que storage/logs/ y bootstrap/cache/ no estén incluidos

Próximas mejoras (V2)
autenticación
roles
revisión manual de eventos
alertas
mejoras de UX
despliegue en servidor