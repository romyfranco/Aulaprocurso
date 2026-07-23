# VoranaPro

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
| Administración | `admin@voranapro.test` |
| Instructor | `instructor@voranapro.test` |
| Estudiante | `estudiante@voranapro.test` |

## Verificación

```bash
php artisan test
npm run build
```

Las pruebas usan SQLite en memoria para no modificar los datos locales de demostración.

## Presentaciones Reveal.js

Cada tema admite un ZIP Reveal.js de hasta 100 MB. El paquete debe contener un único
\`index.html\` en la raíz o dentro de una sola carpeta contenedora. Los archivos se validan
y extraen en almacenamiento privado; la versión anterior continúa activa hasta que la
nueva termina correctamente.

Configuración de producción:

\`\`\`dotenv
REVEAL_HOST=slides.cursos.teslapanama.com
REVEAL_URL=https://slides.cursos.teslapanama.com
REVEAL_PARENT_ORIGIN=https://cursos.teslapanama.com
REVEAL_DISK=reveal
REVEAL_STORAGE_ROOT=/home/u322679524/reveal-storage
REVEAL_ARCHIVE_MAX_BYTES=104857600
REVEAL_EXTRACTED_MAX_BYTES=314572800
REVEAL_MAX_FILES=5000
REVEAL_RATE_LIMIT_PER_MINUTE=6000
REVEAL_TOKEN_TTL_MINUTES=120
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
\`\`\`

En Hostinger, \`cursos.teslapanama.com\` y \`slides.cursos.teslapanama.com\` deben apuntar
al mismo directorio público de Laravel y ambos deben tener SSL. \`SESSION_DOMAIN\` debe
quedar vacío para que la cookie principal sea exclusiva del dominio del aula.
\`REVEAL_STORAGE_ROOT\` debe apuntar a una carpeta privada fuera de \`public_html\` para
que los despliegues de Git no eliminen los ZIP ni las presentaciones extraídas.

Después de publicar una versión:

\`\`\`bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
\`\`\`

El Cron de hPanel debe ejecutarse cada minuto y consumir solamente los trabajos pendientes:

\`\`\`bash
/usr/bin/php /home/u322679524/domains/cursos.teslapanama.com/public_html/artisan queue:work --stop-when-empty --tries=3 --timeout=300 --max-time=50
\`\`\`

Si la ruta asignada por Hostinger es distinta, debe conservarse el comando y cambiar solo
la ruta absoluta hasta \`artisan\`.
