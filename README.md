# Interfaz Web Laravel EPP

## Descripción general

`deteccion-epp-yolo` es la interfaz web Laravel para consultar, revisar y reportar eventos generados por el detector Python YOLO EPP. La aplicación no ejecuta inferencia; consume eventos y evidencias persistidos en MySQL/MariaDB por `mvp-yolo-epp`.

Permite:

- visualizar KPIs operativos en dashboard;
- listar eventos detectados;
- ver evidencia asociada;
- revisar eventos abiertos y cerrados;
- registrar resolución humana de incumplimientos;
- validar manualmente calidad de detección;
- generar reportes y exportaciones CSV/PDF;
- administrar usuarios;
- auditar actividad de usuarios.

## Dependencias externas

La interfaz Laravel requiere:

- PHP compatible con `composer.json`.
- Composer para dependencias PHP.
- Node.js / npm para assets Vite/Tailwind.
- MySQL/MariaDB compartido con el detector.
- Servidor web Apache o Nginx en producción.
- Extensiones PHP requeridas por Laravel, MySQL, sesiones, cache, archivos y DomPDF según el entorno de despliegue.
- Acceso de lectura a la evidencia en disco generada por el detector.
- Permisos de escritura sobre `storage/` y `bootstrap/cache/`.

## Versiones probadas / recomendadas

El proyecto declara versiones principales en `composer.json` y `package.json`, pero no fija una distribución de servidor. Para despliegues nuevos:

- Ubuntu Server 24.04 recomendado para producción Linux.
- PHP 8.3+ requerido por `composer.json`.
- Laravel 13 declarado en `composer.json`.
- Livewire 4 declarado en `composer.json`.
- Node.js LTS recomendado para Vite/Tailwind.
- MySQL/MariaDB compatible con el esquema compartido del detector.
- Apache o Nginx recomendado para servir la aplicación en producción.

## Arquitectura

### Laravel

La aplicación usa Laravel 13 según `composer.json`, rutas web protegidas por middleware `auth` y `active`, controladores HTTP, modelos Eloquent y configuración en `config/epp.php`.

Modelos principales:

- `App\Models\EppEvent`: tabla `epp_events`.
- `App\Models\EppEventEvidence`: tabla `epp_event_evidence`.
- `App\Models\EventAction`: acciones humanas sobre eventos.
- `App\Models\User`: usuarios con rol y permisos.
- `App\Models\UserActivityLog`: auditoría de actividad.

### Blade

Las vistas principales están en `resources/views`. Blade se usa para dashboard, detalle de eventos, eventos abiertos/cerrados, revisión manual, reportes, usuarios, layout y componentes.

### Tailwind / Vite

Los assets se gestionan con Vite y Tailwind:

```bash
npm run dev
npm run build
```

### Livewire

Livewire aplica donde existen componentes explícitos:

- `app/Livewire/Events/Index.php` para `/events`;
- `app/Livewire/Events/Open.php` para una vista Livewire de eventos abiertos;
- componentes de settings/autenticación del starter kit.

No todas las pantallas son Livewire. Varias vistas usan Blade normal con controladores, paginación Laravel y `withQueryString()`.

## Flujo

```text
evento detector
-> MySQL
-> visualización Laravel
-> validación/resolución manual
-> KPIs
```

1. Python inserta un evento en `epp_events`.
2. Python inserta rutas de evidencia en `epp_event_evidence`.
3. Laravel consulta eventos con Eloquent.
4. El usuario visualiza evidencia mediante rutas protegidas.
5. Supervisores/admins pueden resolver eventos abiertos.
6. Usuarios con permiso pueden validar manualmente detecciones.
7. Dashboard y reportes calculan métricas desde la base.

## Vistas principales

### `/dashboard`

Controlador: `DashboardController@index`.

Muestra KPIs de eventos `violation_started` dentro de un rango de fechas. Por defecto usa los últimos 7 días. Calcula total de eventos, eventos no conformes, pendientes humanos, resueltos humanos y últimos 10 eventos.

### `/events`

Vista Livewire `events.index` mediante `App\Livewire\Events\Index`. Lista eventos con filtros por fecha, cámara, escenario, tipo, estado y búsqueda por `event_id`. Por defecto, cuando no se selecciona tipo específico, filtra `violation_started`.

### `/events/review`

Controlador: `EventReviewController@index`. Pantalla de validación manual de detecciones. Permite filtrar por `manual_status`, `detected_status`, cámara, zona y fechas. Calcula validados, pendientes, precisión y tasa de falsos positivos.

### `/events/open`

Controlador: `OpenEventController@index`. Lista eventos no conformes con `human_review_status=pending`. Permite filtrar por cámara, escenario y búsqueda por `event_id` o `sequence_id`.

### `/events/closed`

Controlador: `OpenEventController@closed`. Lista eventos no conformes con `human_review_status=resolved`. Ordena por `human_resolved_at`.

### `/reports`

Controlador: `ReportController@index`. Genera reportes por fecha, cámara, escenario y tipo. Por defecto usa `violation_started`. Si se solicita `event_type`, solo acepta `violation_started` o `violation_resolved`; `compliance_observed` queda excluido de reportes operativos.

### `/users`

Controlador: `UserController` como resource, excepto `show`. Permite crear, editar, activar y deshabilitar usuarios. Requiere `manage_users`.

### `/activity-logs`

Controlador: `ActivityLogController@index`. Lista actividad de usuarios. Requiere `view_user_activity_logs`. También existe exportación CSV.

## Controladores

### `DashboardController`

Construye métricas operativas del dashboard. Su consulta base filtra `event_confirmed_at` dentro del rango y `event_type = violation_started`. Por diseño, no incluye `compliance_observed`.

### `ReportController`

Construye reportes y exportaciones CSV/PDF. `buildBaseQuery()` aplica rango de fechas, cámara, escenario y restringe `event_type` a `violation_started` o `violation_resolved`. Si el filtro no es válido o viene como `all`, usa `violation_started`.

### `EventReviewController`

Gestiona validación manual de detecciones. `index()` filtra eventos, calcula métricas y pagina con `withQueryString()`. `store()` actualiza `manual_status`, `manual_validated_at` y `manual_validated_by`.

## Campos clave

- `event_id`: identificador único generado por Python. Se usa como clave lógica para relaciones y rutas.
- `sequence_id`: correlativo numérico asignado al insertar. Laravel lo muestra como `EVT-000001`.
- `event_type`: `violation_started`, `violation_resolved` o `compliance_observed`.
- `status`: estado confirmado del detector (`compliant` o `non_compliant`).
- `observed_status`: estado observado en el frame que originó el evento.
- `manual_status`: validación manual de calidad de detección.
- `human_review_status`: gestión humana del incumplimiento (`pending` o `resolved`).

## Validación manual

Estados válidos:

- `correct`: la detección coincide con la revisión humana.
- `incorrect`: la detección no coincide con la revisión humana.
- `false_positive`: el sistema generó un evento que no debió contarse como caso válido.
- `not_evaluable`: la evidencia no permite decidir.

La validación manual no modifica el evento original; agrega auditoría en `manual_status`, `manual_validated_at` y `manual_validated_by`.

## Dashboard KPIs

El dashboard se basa en `violation_started`:

- total de eventos;
- eventos no conformes;
- pendientes humanos;
- resueltos humanos.

Las métricas de precisión y falsos positivos están en `/events/review`:

- precisión = `correct / total_validated * 100`;
- falsos positivos = `false_positive / total_validated * 100`;
- pendientes = eventos sin `manual_status`;
- eventos validados = eventos con `manual_status`.

## Paginación y filtros

Patrones usados:

- Livewire para `/events`, con `WithPagination`, `queryString` y reset de página al cambiar filtros.
- Blade/controladores para `/events/review`, `/events/open`, `/events/closed` y `/reports`, usando `paginate(...)->withQueryString()`.

Plantilla Livewire personalizada:

```text
resources/views/vendor/livewire/epp-pagination.blade.php
```

Contenedor:

```text
custom-pagination-wrapper
```

Al modificar filtros, preserve `withQueryString()` o `queryString` para no perder estado al paginar.

## Roles y permisos

Los permisos están en `config/permissions.php`.

- `admin`: acceso completo, incluida administración de usuarios.
- `supervisor`: dashboard, reportes, eventos, exportaciones, actividad, eventos abiertos, resolución y revisión manual.
- `operator`: dashboard, eventos, detalle, eventos abiertos y resolución.
- `viewer`: dashboard, eventos y detalle.

Acceso a `/events/review` requiere:

```text
review_detection_events
```

## Evidencias

`EventEvidenceController` sirve evidencia protegida:

- `/media/events/{eventId}/annotated`;
- `/media/events/{eventId}/full`;
- `/media/events/{eventId}/crop`;
- `/media/events/{eventId}/video`.

Laravel toma la ruta relativa guardada en `epp_event_evidence`, la combina con `config('epp.project_base_path')` y sirve el archivo si existe.

Configure:

```env
EPP_PROJECT_BASE_PATH=/opt/epp-detector
EPP_EVIDENCE_BASE_PATH=/opt/epp-detector/evidence
EPP_CAMERAS_JSON_PATH=/opt/epp-detector/config/cameras.json
EPP_SCENARIOS_JSON_PATH=/opt/epp-detector/config/scenarios.json
```

## Cómo ejecutar Laravel

### Instalación

```bash
cd deteccion-epp-yolo
composer install
npm install
cp .env.example .env
php artisan key:generate
```

En Windows use `copy .env.example .env`.

### Base de datos

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=deteccion_epp
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate --force
```

Nota: las migraciones actuales agregan campos sobre `epp_events`; asegúrese de que la tabla base del detector exista antes de correr migraciones dependientes.

### Desarrollo

```bash
php artisan serve
npm run dev
```

### Producción

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
```

Permisos:

```bash
chmod -R ug+rw storage bootstrap/cache
```

## Troubleshooting

### Permisos

Si Laravel no escribe logs, cache o sesiones, revise `storage/` y `bootstrap/cache/`.

### Cache

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Imágenes de evidencia

Revisar `EPP_PROJECT_BASE_PATH`, rutas en `epp_event_evidence`, permisos de lectura del servidor web y relación `evidence` del evento.

### Paginación

Si al cambiar página se pierden filtros, revise `queryString` en Livewire y `withQueryString()` en controladores Blade.

### Filtros

Si un reporte muestra menos eventos de lo esperado, recuerde que `ReportController` excluye `compliance_observed` y por defecto usa `violation_started`.
