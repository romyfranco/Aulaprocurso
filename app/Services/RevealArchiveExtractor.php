<?php

namespace App\Services;

use App\Exceptions\InvalidRevealArchive;
use Illuminate\Filesystem\Filesystem;
use ZipArchive;

class RevealArchiveExtractor
{
    public function __construct(private readonly Filesystem $files) {}

    /**
     * @return array{entry_path: string, extracted_size: int, file_count: int}
     */
    public function extract(string $archivePath, string $destination): array
    {
        if (! is_file($archivePath)) {
            throw new InvalidRevealArchive('El archivo ZIP ya no está disponible.');
        }

        if (filesize($archivePath) > config('reveal.archive_max_bytes')) {
            throw new InvalidRevealArchive('El ZIP supera el límite de 100 MB.');
        }

        $zip = new ZipArchive;
        $opened = $zip->open($archivePath, ZipArchive::CHECKCONS);

        if ($opened !== true) {
            throw new InvalidRevealArchive('No se pudo abrir el ZIP o el archivo está dañado.');
        }

        try {
            $manifest = $this->inspect($zip);

            $this->files->deleteDirectory($destination);
            $this->files->ensureDirectoryExists($destination);
            $this->writeFiles($zip, $manifest['files'], $destination);
            $this->validateExtractedPresentation($destination);

            return [
                'entry_path' => 'index.html',
                'extracted_size' => $manifest['extracted_size'],
                'file_count' => $manifest['file_count'],
            ];
        } catch (\Throwable $exception) {
            $this->files->deleteDirectory($destination);
            throw $exception;
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array{files: array<int, array{index: int, target: string, size: int, directory: bool}>, extracted_size: int, file_count: int}
     */
    private function inspect(ZipArchive $zip): array
    {
        $maxFiles = config('reveal.max_files');
        $maxBytes = config('reveal.extracted_max_bytes');
        $entries = [];
        $fileNames = [];
        $indexCandidates = [];
        $extractedSize = 0;
        $fileCount = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);

            if (! is_array($stat) || ! isset($stat['name'])) {
                throw new InvalidRevealArchive('El ZIP contiene una entrada que no se puede leer.');
            }

            $rawName = (string) $stat['name'];
            $directory = str_ends_with(str_replace('\\', '/', $rawName), '/');
            $normalized = $this->normalizePath($rawName, $directory);

            $this->rejectSymlink($zip, $index);

            if (! $directory) {
                $this->validateFileName($normalized);
                $fileCount++;
                $extractedSize += max(0, (int) ($stat['size'] ?? 0));

                if ($fileCount > $maxFiles) {
                    throw new InvalidRevealArchive("El ZIP contiene más de {$maxFiles} archivos.");
                }

                if ($extractedSize > $maxBytes) {
                    throw new InvalidRevealArchive('El contenido descomprimido supera el límite de 300 MB.');
                }

                $fileNames[] = $normalized;

                if (strtolower(basename($normalized)) === 'index.html') {
                    $indexCandidates[] = $normalized;
                }
            }

            $entries[] = [
                'index' => $index,
                'target' => $normalized,
                'size' => max(0, (int) ($stat['size'] ?? 0)),
                'directory' => $directory,
            ];
        }

        if (count($indexCandidates) !== 1) {
            throw new InvalidRevealArchive('El ZIP debe contener un único archivo index.html.');
        }

        $entry = $indexCandidates[0];
        $entryParts = explode('/', $entry);
        $prefix = null;

        if (count($entryParts) === 2) {
            $prefix = $entryParts[0].'/';

            foreach ($fileNames as $fileName) {
                if (! str_starts_with($fileName, $prefix)) {
                    throw new InvalidRevealArchive('Cuando index.html está dentro de una carpeta, todos los archivos deben estar en esa misma carpeta.');
                }
            }
        } elseif (count($entryParts) !== 1) {
            throw new InvalidRevealArchive('index.html solo puede estar en la raíz o dentro de una única carpeta superior.');
        }

        $targets = [];

        foreach ($entries as &$item) {
            if ($prefix && str_starts_with($item['target'], $prefix)) {
                $item['target'] = substr($item['target'], strlen($prefix));
            }

            if ($item['target'] === '') {
                continue;
            }

            $key = strtolower(rtrim($item['target'], '/'));

            if (isset($targets[$key])) {
                throw new InvalidRevealArchive('El ZIP contiene rutas duplicadas.');
            }

            $targets[$key] = true;
        }
        unset($item);

        return compact('entries', 'extractedSize', 'fileCount') + [
            'files' => $entries,
            'extracted_size' => $extractedSize,
            'file_count' => $fileCount,
        ];
    }

    /**
     * @param  array<int, array{index: int, target: string, size: int, directory: bool}>  $entries
     */
    private function writeFiles(ZipArchive $zip, array $entries, string $destination): void
    {
        foreach ($entries as $entry) {
            if ($entry['target'] === '') {
                continue;
            }

            $target = $destination.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $entry['target']);

            if ($entry['directory']) {
                $this->files->ensureDirectoryExists($target);

                continue;
            }

            $this->files->ensureDirectoryExists(dirname($target));
            $source = $zip->getStream($zip->getNameIndex($entry['index']));

            if (! is_resource($source)) {
                throw new InvalidRevealArchive("No se pudo leer {$entry['target']}.");
            }

            $output = fopen($target, 'wb');

            if (! is_resource($output)) {
                fclose($source);
                throw new InvalidRevealArchive("No se pudo extraer {$entry['target']}.");
            }

            $written = stream_copy_to_stream($source, $output, $entry['size'] + 1);
            fclose($source);
            fclose($output);

            if ($written === false || $written !== $entry['size']) {
                throw new InvalidRevealArchive("El archivo {$entry['target']} no se extrajo completamente.");
            }
        }
    }

    private function normalizePath(string $path, bool $directory): string
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $path)) {
            throw new InvalidRevealArchive('El ZIP contiene caracteres de control en una ruta.');
        }

        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            throw new InvalidRevealArchive('El ZIP contiene una ruta absoluta no permitida.');
        }

        $path = preg_replace('#/+#', '/', str_replace('\\', '/', $path)) ?? '';
        $parts = explode('/', rtrim($path, '/'));

        if ($path === '' || collect($parts)->contains(fn (string $part) => $part === '' || $part === '.' || $part === '..')) {
            throw new InvalidRevealArchive('El ZIP contiene una ruta insegura.');
        }

        return implode('/', $parts).($directory ? '/' : '');
    }

    private function rejectSymlink(ZipArchive $zip, int $index): void
    {
        $attributes = 0;
        $operatingSystem = 0;

        if (! $zip->getExternalAttributesIndex($index, $operatingSystem, $attributes)) {
            return;
        }

        $fileType = ($attributes >> 16) & 0170000;

        if ($fileType === 0120000) {
            throw new InvalidRevealArchive('El ZIP contiene enlaces simbólicos, que no están permitidos.');
        }
    }

    private function validateFileName(string $path): void
    {
        $name = strtolower(basename($path));
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (in_array($name, config('reveal.forbidden_names'), true) || in_array($extension, config('reveal.forbidden_extensions'), true)) {
            throw new InvalidRevealArchive("El archivo {$path} no está permitido.");
        }

        if (! in_array($extension, config('reveal.allowed_extensions'), true)) {
            throw new InvalidRevealArchive("El tipo de archivo .{$extension} no está permitido en una presentación.");
        }
    }

    private function validateExtractedPresentation(string $destination): void
    {
        $entryPath = $destination.DIRECTORY_SEPARATOR.'index.html';
        $html = file_get_contents($entryPath);

        if (! is_string($html) || stripos($html, '<html') === false) {
            throw new InvalidRevealArchive('index.html no parece ser un documento HTML válido.');
        }

        foreach ($this->files->allFiles($destination) as $file) {
            $extension = strtolower($file->getExtension());

            if (! in_array($extension, ['html', 'htm', 'css'], true)) {
                continue;
            }

            $contents = $file->getContents();

            if (preg_match('/\bhttp:\/\//i', $contents)) {
                throw new InvalidRevealArchive('La presentación contiene recursos HTTP; usa HTTPS o incluye el recurso en el ZIP.');
            }

            if (in_array($extension, ['html', 'htm'], true)) {
                preg_match_all('/\b(?:src|href|poster|data-background(?:-image|-video|-iframe)?)\s*=\s*(["\'])(.*?)\1/i', $contents, $matches);

                foreach ($matches[2] ?? [] as $reference) {
                    $this->validateReference($reference);
                }
            } else {
                preg_match_all('/(?:url\(\s*|@import\s+)(["\']?)([^)"\'\s;]+)\1/i', $contents, $matches);

                foreach ($matches[2] ?? [] as $reference) {
                    $this->validateReference($reference);
                }
            }
        }
    }

    private function validateReference(string $reference): void
    {
        $reference = trim(html_entity_decode($reference, ENT_QUOTES | ENT_HTML5));

        if ($reference === '' || str_starts_with($reference, '#') || str_starts_with($reference, '?') || str_starts_with($reference, 'data:') || str_starts_with($reference, 'https://') || str_starts_with($reference, '//')) {
            return;
        }

        if (str_starts_with($reference, '/') || preg_match('/(^|\/)\.\.($|\/)/', $reference) || preg_match('/^[a-z][a-z0-9+.-]*:/i', $reference)) {
            throw new InvalidRevealArchive("La referencia {$reference} debe ser una ruta relativa o una URL HTTPS.");
        }
    }
}
