# Memoria Tecnica Interna: Roig Arena

## 1. Resumen Ejecutivo
Este documento tiene como proposito describir la arquitectura interna, el modelo de datos y el flujo tecnico de la aplicacion Roig Arena, a partir del analisis de su codigo fuente en Laravel. La finalidad es demostrar una comprension completa de como se implementan las funcionalidades de catalogo de eventos, seleccion de asientos, reserva temporal y compra de entradas.

La aplicacion actua como plataforma web/API para la gestion de un recinto multiespacio. Su dominio se centra en entidades fisicas (sectores y asientos), entidades de programacion (eventos y artistas) y operaciones transaccionales de ticketing (estado de asientos, reservas y entradas). El sistema diferencia claramente entre acceso publico (consulta de eventos/sectores/artistas), usuario autenticado (reservas, compras, consulta de entradas) y administrador (alta/edicion/borrado de eventos, sectores y artistas).

El valor principal para el recinto es la trazabilidad y control de disponibilidad por evento y por asiento, con logica de negocio para evitar dobles reservas, controlar expiracion temporal de bloqueos y transformar reservas en ventas confirmadas. La aplicacion combina interfaz web Blade y endpoints REST protegidos con Sanctum, permitiendo un uso hibrido orientado tanto a navegacion tradicional como a clientes API.

## 2. Analisis Arquitectonico

### 2.1. Patron de Diseno Principal
La aplicacion sigue el patron Modelo-Vista-Controlador (MVC).

Laravel implementa este patron mediante modelos Eloquent en app/Models, controladores HTTP en app/Http/Controllers, vistas Blade en resources/views y definicion de rutas en routes/web.php y routes/api.php. En este proyecto, la separacion MVC aporta beneficios directos: la logica de dominio (disponibilidad, reservas, venta de entradas) queda encapsulada en modelos y servicios; la capa de presentacion se mantiene en vistas Blade especificas por modulo (eventos, compra, auth, artistas); y la orquestacion de peticiones se centraliza en controladores, facilitando mantenimiento y evolucion del sistema.

### 2.2. Estructura de Directorios y Organizacion del Codigo

| Carpeta | Rol en el proyecto |
|---|---|
| app/ | Nucleo de aplicacion y logica de dominio. |
| database/ | Persistencia estructural (migraciones, factories, seeders). |
| resources/ | Capa de presentacion (vistas Blade). |
| routes/ | Contrato de entrada HTTP/CLI (web, api, console). |

Detalle por area:

- app/
  - app/Models define entidades de negocio (Evento, Sector, Asiento, EstadoAsiento, Entrada, Precio, Artista, User) y relaciones Eloquent.
  - app/Http/Controllers contiene controladores para API y web; incluye subespacios Auth y Web para separar responsabilidades.
  - app/Http/Middleware incluye IsAdmin para autorizacion por rol administrador.
  - app/Services encapsula reglas complejas de dominio (ReservaService, CompraService, SectorGeometryService, LiberarReservasService).
  - app/Http/Resources implementa transformadores JSON para respuestas API tipadas.

- database/
  - database/migrations define el esquema completo (tablas del dominio Roig Arena y tablas base de Laravel como users, jobs, cache, sessions, personal_access_tokens).
  - database/factories y database/seeders soportan datos de prueba/poblado.

- resources/
  - resources/views agrupa vistas Blade por contexto funcional (auth, compra, eventos, artistas, layouts), ademas de home.blade.php.

- routes/
  - routes/web.php define navegacion web y acciones HTML.
  - routes/api.php define endpoints REST para clientes autenticados/no autenticados.
  - routes/console.php concentra comandos de consola.

## 3. Modelo de Datos

### 3.1. Diagrama de Entidades y Relaciones (Texto)
Modelos Eloquent identificados y relaciones observadas:

### User
- Atributos relevantes: nombre, apellido, email, password, is_admin.
- Relaciones: hasMany EstadoAsiento (reservas), hasMany Entrada (entradas compradas).
- Rasgos tecnicos: autenticable, HasApiTokens (Sanctum), SoftDeletes.

### Evento
- Atributos relevantes: nombre, descripcion_corta, descripcion_larga, poster_url, poster_ancho_url, fecha, hora.
- Relaciones: hasMany Precio, belongsToMany Sector a traves de precios (con pivote precio/disponible), hasMany EstadoAsiento, hasMany Entrada, belongsToMany Artista a traves de artista_evento.
- Rasgos tecnicos: SoftDeletes, scopes de fechas (futuros, pasados, delMes), metodos de disponibilidad agregada.

### Sector
- Atributos relevantes: nombre, descripcion, color_hex, activo, limites de rectangulo (fila_inicio/fin, columna_inicio/fin), cantidad_filas, cantidad_columnas.
- Relaciones: hasMany Asiento, hasMany Precio, belongsToMany Evento via precios.
- Rasgos tecnicos: scope activos, utilidades para conteo/disponibilidad por evento.

### Asiento
- Atributos relevantes: sector_id, fila, numero.
- Relaciones: belongsTo Sector, hasMany EstadoAsiento, hasMany Entrada.
- Rasgos tecnicos: metodos de evaluacion de disponibilidad por evento.

### Precio
- Atributos relevantes: evento_id, sector_id, precio, disponible.
- Relaciones: belongsTo Evento, belongsTo Sector.
- Papel: tabla puente Evento-Sector con informacion de precio y habilitacion comercial.

### EstadoAsiento
- Atributos relevantes: evento_id, asiento_id, user_id, estado (DISPONIBLE/RESERVADO/OCUPADO), reservado_hasta.
- Relaciones: belongsTo Evento, Asiento y User.
- Papel: estado transaccional de cada asiento por evento para gestionar bloqueos temporales y ventas.

### Entrada
- Atributos relevantes: user_id, evento_id, asiento_id, precio_pagado, codigo_qr, descargada, utilizada.
- Relaciones: belongsTo User, Evento y Asiento.
- Papel: evidencia de venta confirmada (ticket).

### Artista
- Atributos relevantes: nombre, descripcion, imagen_url.
- Relaciones: belongsToMany Evento via artista_evento.

### Post
- Modelo presente sin logica implementada en el dominio actual.

Relaciones fisicas confirmadas por migraciones:
- uno a muchos: Sector->Asiento, Evento->Precio, Evento->EstadoAsiento, Evento->Entrada, User->Entrada.
- muchos a muchos: Evento<->Artista (artista_evento), Evento<->Sector (precios como pivote enriquecido).
- unicidad de negocio: (evento_id, sector_id) en precios; (evento_id, asiento_id) en estado_asientos y entradas.

## 4. Flujo de Trabajo: El Ciclo de una Peticion
La entrada de una peticion HTTP comienza en public/index.php, donde se carga el autoloader de Composer y se inicializa la aplicacion mediante bootstrap/app.php. Ese bootstrap registra rutas web/api/console y aplica configuracion de middleware, incluyendo el prepend de EnsureFrontendRequestsAreStateful de Sanctum para API y el alias admin apuntando a App\Http\Middleware\IsAdmin.

Desde ese punto, Laravel resuelve la ruta con el Router segun routes/web.php o routes/api.php. En web se encuentran rutas publicas como /eventos y rutas protegidas con auth (por ejemplo /mis-eventos), ademas de grupos admin con auth + admin. En API se distinguen rutas publicas (registro/login y consulta de catalogo) y rutas protegidas con auth:sanctum para reservas, compras y entradas.

Durante el paso por middleware se aplican, segun ruta, autenticacion de sesion o token (auth/auth:sanctum), autorizacion por rol (admin) y, en rutas web, la pila estandar del framework para cookies/sesion/CSRF. Si la peticion supera estas capas, se ejecuta el metodo de controlador destino.

Ejemplo tipico de flujo transaccional: un usuario autenticado llama a POST /api/reservas, ReservaController::store valida entrada y delega en ReservaService::reservarAsiento, que realiza bloqueo pesimista (lockForUpdate) sobre estado_asientos, verifica disponibilidad por sector/evento y persiste estado RESERVADO con caducidad a 15 minutos. Posteriormente, al confirmar compra (CompraController::confirmarCompra o procesarPagoPendiente), el sistema abre transaccion DB, convierte reservas activas en entradas (tabla entradas), marca estado OCUPADO y recalcula disponibilidad del evento mediante Evento::comprobarEvento.

En el caso web de consulta, por ejemplo GET /eventos/{evento}, PaginaController::eventosShow carga relaciones Eloquent (precios.sector, artistas), filtra sectores activos y retorna una vista Blade (eventos.show) con los datos estructurados. Finalmente Laravel serializa la respuesta (HTML o JSON) y la envia al cliente.

## 5. Analisis Tecnico y Decisiones Clave
- ORM y Base de Datos: se utiliza Eloquent ORM como capa de mapeo objeto-relacional. Esta eleccion permite modelar relaciones complejas del dominio de ticketing (pivotes con datos, scopes, eager loading, metodos de dominio) de forma declarativa y mantenible. Las migraciones definen restricciones de integridad (claves foraneas, unicidad por evento/asiento o evento/sector) que refuerzan reglas de negocio criticas.
- Gestion de Dependencias: Composer gestiona dependencias PHP. En composer.json destacan laravel/framework (estructura base MVC y ecosistema), laravel/sanctum (autenticacion API por tokens y SPA stateful), y laravel/tinker (inspeccion interactiva). En desarrollo se usan phpunit, mockery, faker, pint, sail y collision para pruebas/calidad/entorno.
- Autenticacion y Autorizacion: se combina autenticacion web por sesion (guard web) con autenticacion API mediante Sanctum (rutas auth:sanctum y creacion/revocacion de tokens en AuthController). La autorizacion de administrador se implementa con middleware personalizado IsAdmin basado en el campo is_admin del usuario. No se han encontrado politicas (Policies) definidas en app/Policies.
- Entorno y Configuracion: la configuracion por entorno se externaliza en .env/.env.example (APP_*, DB_*, SESSION_*, CACHE_*, QUEUE_*, REDIS_*, MAIL_*, AWS_*). El proyecto viene preparado para DB por defecto sqlite, sesiones en base de datos, cache en base de datos y colas en base de datos. Aunque existe infraestructura de cola (tablas jobs/failed_jobs y script de queue:listen en composer), no se han identificado clases Job propias en app/Jobs.

En cuanto a otros componentes Laravel, se confirma uso de Resources API (app/Http/Resources), Service Container por inyeccion en controladores/servicios y transacciones DB. No se han identificado clases de eventos/listeners personalizadas en app/Events ni app/Listeners.

## 6. Conclusion y Reflexion
El analisis del codigo confirma una arquitectura coherente para un caso real de recinto multiespacio: separacion clara MVC, dominio bien modelado en Eloquent, rutas web/API diferenciadas, y reglas de negocio transaccionales para evitar inconsistencias en reservas y ventas. Tambien se observa una distincion funcional nitida entre usuario publico, usuario autenticado y administrador, con controles de acceso acordes a cada operacion.

Laravel resulta una eleccion solida para este tipo de plataforma porque combina productividad con rigor tecnico: facilita evolucion del modelo de datos mediante migraciones, mantiene la logica modular mediante controladores/servicios/modelos, y aporta un ecosistema maduro para autenticacion, pruebas y despliegue. Esta base favorece escalabilidad funcional (nuevos tipos de evento, nuevas reglas comerciales) y mantenibilidad a medio/largo plazo.
