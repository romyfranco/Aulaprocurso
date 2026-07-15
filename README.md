# AulaPro

Plataforma educativa construida desde el esquema `plataforma_educacion_schema_FINAL.json`. Incluye tres experiencias independientes en Filament 5.6 sobre Laravel 12:

- **Administración (`/admin`)**: usuarios, cursos, temas, matrículas, evaluaciones, progreso y certificados.
- **Instructor (`/instructor`)**: cursos asignados, contenido multimedia, constructor de evaluaciones, calificación manual, progreso e intentos extra.
- **Estudiante (`/student`)**: cursos inscritos, evaluaciones, desbloqueo secuencial, progreso y certificados verificables.

## Funcionalidad implementada

- Modelo completo de 13 tablas y sus relaciones.
- Acceso separado por rol y consultas limitadas al alcance de cada usuario.
- Máximo de dos intentos base, con concesiones extra acumulables e ilimitadas por instructor.
- Calificación automática para selección múltiple y verdadero/falso.
- Flujo de revisión manual para respuestas cortas y ensayos.
- Desbloqueo del siguiente tema cuando el intento anterior queda calificado.
- Cálculo automático del porcentaje de progreso.
- Emisión de certificado al 100%, con QR, PDF y verificación pública.
- Carga de imágenes, videos, PDF y presentaciones mediante Spatie Media Library.
- Dashboards, tarjetas, badges, iconos y temas visuales propios para cada panel.
- Datos de demostración y pruebas automatizadas de reglas de negocio y pantallas.

## Puesta en marcha

Requisitos: PHP 8.2+, Composer, Node.js, SQLite o MySQL 8+, y Chrome para generar los PDF.

```bash
composer install
npm install
php artisan key:generate
php artisan storage:link
php artisan migrate:fresh --seed
npm run build
composer run dev
```

Para generar los PDF pendientes, debe mantenerse activo el trabajador de tareas incluido en `composer run dev`, o ejecutar:

```bash
php artisan queue:work
```

## Accesos de demostración

Todos usan la contraseña `demo12345`.

| Panel | Correo |
|---|---|
| Administración | `admin@aulapro.test` |
| Instructor | `instructor@aulapro.test` |
| Estudiante | `estudiante@aulapro.test` |

## Verificación

```bash
php artisan test
npm run build
```

Las pruebas usan SQLite en memoria para no modificar los datos locales de demostración.
