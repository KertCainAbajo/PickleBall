<?php

namespace App\Console;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs before `php artisan serve` on a local machine so the app you open is always current:
 *  1. applies any new database migrations,
 *  2. rebuilds the CSS/JS if views, styles or components changed since the last build,
 *  3. removes a leftover Vite "hot" file when Vite isn't actually running.
 *
 * Registered in AppServiceProvider for the local environment only.
 * Set AUTO_UPDATE_ON_SERVE=false in .env to turn it off.
 */
class PrepareLocalServer
{
    /** Folders/files whose changes require a new asset build (Tailwind scans the views and components). */
    private const ASSET_SOURCES = [
        'resources/css',
        'resources/js',
        'resources/views',
        'app/Livewire',
        'vite.config.js',
        'package.json',
    ];

    public function handle(CommandStarting $event): void
    {
        if ($event->command !== 'serve') {
            return;
        }

        $out = $event->output;
        $out->writeln('');
        $out->writeln('  <fg=yellow>PickleBall</> getting everything up to date...');

        $this->migrate($out);
        $this->clearStaleHotFile($out);
        $this->buildAssetsIfStale($out);

        $out->writeln('');
    }

    private function migrate(OutputInterface $out): void
    {
        $exit = Artisan::call('migrate', ['--force' => true]);
        $result = trim(Artisan::output());

        if ($exit !== 0) {
            $out->writeln('  <fg=red>Database update failed:</>');
            $out->writeln($result);

            return;
        }

        $out->writeln(str_contains($result, 'Nothing to migrate')
            ? '  <fg=green>✓</> Database is up to date'
            : '  <fg=green>✓</> Database updated');
    }

    /** A "hot" file means "load assets from the Vite dev server". If Vite isn't running, the page loses its styles. */
    private function clearStaleHotFile(OutputInterface $out): void
    {
        $hot = public_path('hot');

        if (! is_file($hot)) {
            return;
        }

        $url = trim((string) file_get_contents($hot));
        $parts = parse_url($url);
        $host = trim($parts['host'] ?? 'localhost', '[]');
        $port = $parts['port'] ?? 5173;

        $socket = @fsockopen($host, $port, $errno, $errstr, 0.5);

        if ($socket) {
            fclose($socket);
            $out->writeln('  <fg=green>✓</> Vite dev server is running (live style updates)');

            return;
        }

        @unlink($hot);
        $out->writeln('  <fg=green>✓</> Removed a leftover Vite file (Vite is not running)');
    }

    private function buildAssetsIfStale(OutputInterface $out): void
    {
        if (is_file(public_path('hot'))) {
            return; // Vite dev is serving assets live; no build needed.
        }

        $manifest = public_path('build/manifest.json');
        $builtAt = is_file($manifest) ? filemtime($manifest) : 0;

        if ($builtAt >= $this->newestSourceChange()) {
            $out->writeln('  <fg=green>✓</> Styles are up to date');

            return;
        }

        $out->writeln('  <fg=yellow>…</> Styles changed, rebuilding (a few seconds)...');

        $result = Process::path(base_path())->timeout(300)->run('npm run build');

        if ($result->failed()) {
            $out->writeln('  <fg=red>Style build failed.</> Run "npm run build" to see the error.');
            $out->writeln(trim($result->errorOutput()) ?: trim($result->output()));

            return;
        }

        $out->writeln('  <fg=green>✓</> Styles rebuilt');
    }

    /** Latest modification time among the asset sources. */
    private function newestSourceChange(): int
    {
        $newest = 0;

        foreach (self::ASSET_SOURCES as $relative) {
            $path = base_path($relative);

            if (is_file($path)) {
                $newest = max($newest, filemtime($path));

                continue;
            }

            if (! is_dir($path)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));

            foreach ($files as $file) {
                $newest = max($newest, $file->getMTime());
            }
        }

        return $newest;
    }
}
