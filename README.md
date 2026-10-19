# Gestor de Reservas de Alojamientos

Aplicación web y API REST para gestionar reservas de alojamientos: listado con filtros combinables, alta de reservas, detalle con historial cronológico de cambios y cancelación de reservas. Está construida en **PHP 8 nativo sin framework** (sin Laravel, Symfony ni similar), aplicando arquitectura por capas, PDO con MySQL/MariaDB, vistas con **Twig** e interactividad asíncrona mediante **JavaScript Vanilla** (`fetch`).

**Demo desplegada:** *[AÑADIR_AQUÍ_LA_URL_DE_TU_DEPLOY]*

---

## Índice

1. [Requisitos e instalación local](#1-requisitos-e-instalación-local)
2. [Estructura del proyecto](#2-estructura-del-proyecto)
3. [Referencia de la API](#3-referencia-de-la-api)
4. [Decisiones técnicas](#4-decisiones-técnicas)
5. [Medidas de seguridad](#5-medidas-de-seguridad)
6. [Uso de `|raw`](#6-uso-de-raw)
7. [Qué haría diferente con más tiempo](#7-qué-haría-diferente-con-más-tiempo)
8. [Escalabilidad: 1.000 reservas/hora](#8-escalabilidad-1000-reservashora)
9. [Transparencia y asistencia en el desarrollo](#9-transparencia-y-asistencia-en-el-desarrollo)

---

## 1. Requisitos e instalación local

**Requisitos mínimos:**

* PHP ≥ 8.2 con las extensiones `pdo_mysql` y `mbstring` habilitadas.
* MySQL ≥ 5.7 o MariaDB ≥ 10.2 (mediante MAMP, WAMP, XAMPP o nativo).
* Composer 2.x para dependencias de producción (`twig/twig`) y autocarga PSR-4.
* Apache con módulo `mod_rewrite` habilitado (o el servidor interno de PHP).

### Pasos de instalación

```bash
# 1. Clonar el repositorio
git clone https://github.com/TU_USUARIO/TU_REPOSITORIO.git
cd TU_REPOSITORIO

# 2. Instalar dependencias (Twig y autoloader PSR-4)
composer install

# 3. Crear la base de datos e importar el esquema y datos de prueba
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS gestion_reservas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p gestion_reservas < database.sql

# 4. Arrancar en local con el servidor interno de PHP
php -S localhost:8000 -t public
```

Abre en el navegador: <http://localhost:8000>

### Con phpMyAdmin (MAMP / WAMP / XAMPP)

1. Inicia los servidores en MAMP.
2. Accede a phpMyAdmin y crea la base de datos `gestion_reservas` con cotejamiento `utf8mb4_unicode_ci`.
3. Selecciona la base de datos, entra en la pestaña **Importar**, selecciona el archivo `database.sql` de la raíz del proyecto y pulsa en **Importar**.
4. En MAMP con Apache, accede a través de: `http://localhost/Prueba-PHP/public/`. El archivo `.htaccess` configurado en `public/` canaliza el tráfico automáticamente hacia el front controller.

---

## 2. Estructura del proyecto

```text
public/
  index.php                      # Front Controller: enrutador API y vistas HTML
  .htaccess                      # Reglas de reescritura para Apache (mod_rewrite)
  js/
    app.js                       # Vanilla JS: llamadas asíncronas fetch(), DOM y feedback
src/
  Database/
    Database.php                 # Conexión Singleton PDO con MySQL
  Repositories/
    ReservaRepository.php        # Única capa con consultas SQL (sentencias preparadas PDO)
  Services/
    ReservaService.php           # Lógica de negocio, validaciones y transacciones atómicas
  Controllers/
    ReservaApiController.php     # Gestión de peticiones REST y respuestas JSON
    ReservaWebController.php     # Renderizado de vistas HTML con Twig
templates/
  base.html.twig                 # Plantilla base (esqueleto, estilos CSS responsive y alertas)
  reservas/
    index.html.twig              # Vista del listado, filtros dinámicos y modal de alta
    show.html.twig               # Vista de detalle con historial cronológico (timeline)
database.sql                     # DDL de tablas (reserva y reserva_evento) + datos de ejemplo
composer.json                    # Autocarga PSR-4 (App\) y dependencia de Twig
```

### Flujo de una petición

1. La petición entra a `public/index.php` a través de la reescritura de Apache.
2. El enrutador discrimina el método HTTP y la URI mediante expresiones regulares.
3. Se delega al Controller correspondiente (`ReservaApiController` para JSON o `ReservaWebController` para HTML).
4. El controlador invoca al Service, que aplica las validaciones de negocio y controla las transacciones.
5. El servicio se apoya en el Repository, que ejecuta las sentencias SQL preparadas con PDO.

---

## 3. Referencia de la API

Punto de entrada base: `/api`. Todas las respuestas utilizan el tipo `application/json; charset=utf-8`.

**Estructura de respuesta exitosa:**

```json
{"status": "success", "data": "..."}
```

**Estructura de respuesta de error:**

```json
{"status": "error", "message": "..."}
```

| Método | Endpoint | Descripción | Códigos HTTP |
|--------|----------|-------------|--------------|
| `GET` | `/api/ping` | Verificación de estado de la API y conexión a base de datos | 200, 500 |
| `GET` | `/api/reservas` | Listado de reservas con filtros combinables | 200, 500 |
| `GET` | `/api/reservas/{id}` | Detalle de reserva e historial cronológico de eventos | 200, 404, 500 |
| `POST` | `/api/reservas` | Crear una nueva reserva (estado inicial `pendiente`) | 201, 400, 500 |
| `PATCH` | `/api/reservas/{id}/estado` | Cambiar estado: `{"estado": "confirmada" \| "cancelada"}` | 200, 400, 404, 500 |

### Filtros combinables (`GET /api/reservas`)

* `estado`: `pendiente`, `confirmada` o `cancelada`.
* `nombre`: búsqueda parcial de texto sobre el nombre del huésped (`LIKE %...%`).
* `fecha_desde`: filtra estancias a partir de una fecha de entrada dada (`YYYY-MM-DD`).
* `fecha_hasta`: filtra estancias hasta una fecha de entrada límite (`YYYY-MM-DD`).

### Ejemplos con `curl`

**Listar reservas confirmadas:**

```bash
curl -X GET "http://localhost/Prueba-PHP/public/api/reservas?estado=confirmada"
```

**Consultar detalle con timeline:**

```bash
curl -X GET "http://localhost/Prueba-PHP/public/api/reservas/2"
```

**Crear una nueva reserva:**

```bash
curl -X POST "http://localhost/Prueba-PHP/public/api/reservas" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre_huesped": "Ana Martínez",
    "email_huesped": "ana.martinez@example.com",
    "nombre_alojamiento": "Ático Gran Vía",
    "fecha_entrada": "2026-11-20",
    "fecha_salida": "2026-11-25",
    "importe": 450.00,
    "notas": "Check-in tardío"
  }'
```

**Cancelar una reserva:**

```bash
curl -X PATCH "http://localhost/Prueba-PHP/public/api/reservas/1/estado" \
  -H "Content-Type: application/json" \
  -d '{"estado": "cancelada"}'
```

---

## 4. Decisiones técnicas

* **PHP nativo y arquitectura en 3 capas:** para cumplir el requisito de no utilizar frameworks full-stack, se estructuró una separación limpia de responsabilidades (Controller → Service → Repository). Esto aísla el SQL de las reglas de negocio y facilita el mantenimiento del código.
* **Base de datos relacional (MySQL / InnoDB):** se optó por MySQL con motor InnoDB para garantizar integridad referencial (claves foráneas entre `reserva` y `reserva_evento`) y cumplimiento estricto de ACID.
* **Transacciones atómicas PDO:** tanto la creación de una reserva como los cambios de estado se ejecutan en bloques transaccionales (`$db->beginTransaction()`, `$db->commit()`, `$db->rollBack()`). De este modo, es imposible registrar una reserva o modificar un estado sin su correspondiente traza en `reserva_evento`.
* **Control de estados en servidor:** el servicio valida estrictamente las transiciones: una reserva cancelada no puede reactivarse, no se permite reasignar el mismo estado y solo se admiten los estados `confirmada` o `cancelada`.
* **Twig con herencia:** se utilizó `base.html.twig` con bloques extensibles (`{% block content %}`) para centralizar estilos CSS, estructura HTML y notificaciones sin duplicar código.
* **Interactividad asíncrona con Vanilla JS:** toda la comunicación entre la vista y el backend se realiza vía `fetch()`. Se implementó delegación de eventos en la tabla para manejar cancelaciones dinámicas, validaciones de coherencia en cliente previas al envío y renderizado del DOM sin recargar la página.

---

## 5. Medidas de seguridad

* **Protección contra inyección SQL:** todas las consultas a la base de datos en `ReservaRepository` utilizan sentencias preparadas (`PDO::prepare`) con vinculación explícita de parámetros (`:nombre`, `:estado`, etc.).
* **Validación estricta de entrada (server-side):**
  * Validación de correos mediante `filter_var(..., FILTER_VALIDATE_EMAIL)`.
  * Coherencia cronológica verificada con `DateTime::createFromFormat('Y-m-d', ...)`, asegurando que `fecha_salida > fecha_entrada`.
  * Validación de importes positivos numéricos.
* **Prevención de XSS:** Twig tiene el autoescaping activo por defecto (`html`). Adicionalmente, en `app.js` se implementó una función de sanitización (`escapeHTML`) para el contenido insertado dinámicamente en el DOM.
* **CORS y códigos semánticos:** configuración explícita de cabeceras CORS en el Front Controller y emisión de códigos HTTP semánticos (200, 201, 400, 404, 500) según el resultado de cada operación.

---

## 6. Uso de `|raw`

No se utiliza el filtro `|raw` en ninguna de las plantillas Twig del proyecto. Todas las variables que proceden de la base de datos o de la entrada del usuario se escapan automáticamente mediante el motor de Twig. Los formateadores utilizados son exclusivamente `|date("d/m/Y")` para fechas y `|number_format(2, ',', '.')` para importes monetarios.

---

## 7. Qué haría diferente con más tiempo

* **Testing automatizado:** implementar una suite de pruebas con PHPUnit que cubra casos límite en `ReservaService` (fechas invertidas, transiciones ilegales de estado) y tests funcionales de endpoints.
* **Manejo de variables de entorno (`.env`):** integrar `vlucas/phpdotenv` para desacoplar credenciales de base de datos entre entornos de desarrollo y producción sin modificar código fuente.
* **Paginación en servidor:** añadir parámetros `limit` y `offset` en el repositorio para evitar sobrecarga de memoria al escalar a miles de registros.
* **Protección CSRF:** incluir tokens de un solo uso por sesión para peticiones que muten datos (`POST`, `PATCH`).
* **Objetos de dominio / DTOs:** reemplazar el paso de arrays asociativos por clases fuertemente tipadas en PHP 8.3 (`ReservaData`, `FiltroReservasDTO`).

---

## 8. Escalabilidad: 1.000 reservas/hora

Procesar 1.000 reservas por hora representa una carga sostenida moderada (~0,3 escrituras por segundo), pero en escenarios con picos concurrentes convendría adoptar las siguientes medidas:

* **Control de concurrencia (overbooking):** introducir bloqueos pesimistas (`SELECT ... FOR UPDATE`) sobre la consulta de disponibilidad en la misma transacción, o implementar bloqueos distribuidos con Redis, para evitar que dos usuarios reserven el mismo alojamiento en las mismas fechas simultáneamente.
* **Capa de caché de lecturas:** implementar Redis o Memcached para almacenar el resultado de filtros frecuentes y listados, invalidando la caché únicamente ante eventos de escritura (`POST` o `PATCH`).
* **Colas asíncronas de tareas:** desacoplar el envío de correos de confirmación, webhooks o facturación externa mediante un sistema de colas (RabbitMQ o Redis Streams) procesado por workers independientes en segundo plano.
* **Optimización de base de datos:** agregar índices compuestos específicos (`CREATE INDEX idx_reserva_fechas ON reserva(fecha_entrada, fecha_salida)`) e implementar réplicas de lectura (*read replicas*) para descargar la base de datos principal de las consultas del listado.

---

## 9. Transparencia y asistencia en el desarrollo

* **Entorno de trabajo:** PHP 8.3, MySQL, MAMP, Composer, Twig, JavaScript nativo.
* **Uso de asistencia con IA:** durante el desarrollo se utilizó Gemini (Google) como asistente técnico y copiloto para:
  * Contrastar el diseño de la arquitectura por capas en PHP puro.
  * Resolver directivas de reescritura de Apache (`.htaccess`) y configuración del controlador `pdo_mysql` en consola.
  * Validar la estructura semántica de las plantillas Twig y el consumo asíncrono con JavaScript Vanilla.