<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use SimpleXMLElement;

class TestsController extends Controller
{
    private function guardProduction(): void
    {
        if (app()->isProduction()) {
            abort(403, 'Test runner is disabled in production.');
        }
    }

    public function list(): JsonResponse
    {
        $this->guardProduction();
        $base = base_path('tests');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));
        $suites = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') continue;

            $content = file_get_contents($file->getPathname());

            if (!preg_match('/namespace\s+([\w\\\\]+)/', $content, $ns)) continue;
            if (!preg_match('/class\s+(\w+)/', $content, $cl)) continue;

            preg_match_all('/public function (test\w+)\s*\(/', $content, $methods);
            if (empty($methods[1])) continue;

            $class = $ns[1] . '\\' . $cl[1];
            $type = str_contains($class, '\\Feature\\') ? 'Feature' : 'Unit';

            $group = match(true) {
                str_contains($class, '\\Feature\\Admin\\') => 'Admin',
                str_contains($class, '\\Feature\\Site\\')  => 'Site',
                str_contains($class, '\\Feature\\Auth\\')  => 'Site',
                str_contains($class, '\\Unit\\')           => 'Unit',
                default                                    => 'Other',
            };

            $suites[] = [
                'name' => $cl[1],
                'class' => $class,
                'type' => $type,
                'group' => $group,
                'filter' => $cl[1],
                'methods' => array_map(fn($m) => [
                    'name' => ucfirst(str_replace('_', ' ', preg_replace('/^test_/', '', $m))),
                    'filter' => $m,
                ], $methods[1]),
            ];
        }

        usort($suites, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $this->respondWithJson(['suites' => $suites]);
    }

    public function run(Request $request): JsonResponse
    {
        $this->guardProduction();

        $filter = $request->input('filter');
        $tmpFile = sys_get_temp_dir() . '/phpunit_' . uniqid() . '.xml';
        $callsFile = str_replace('.xml', '_calls.json', $tmpFile);

        $phpBin = PHP_BINDIR . '/php';
        $args = [$phpBin, base_path('vendor/bin/phpunit'), '--log-junit', $tmpFile, '--no-progress'];
        if ($filter) {
            $args[] = '--filter=' . $filter;
        }

        $process = Process::path(base_path())
            ->env([
                'APP_ENV' => 'testing',
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => ':memory:',
                'CACHE_STORE' => 'array',
                'SESSION_DRIVER' => 'array',
                'QUEUE_CONNECTION' => 'sync',
                'PHPUNIT_CALLS_FILE' => $callsFile,
            ])
            ->timeout(120)
            ->run($args);

        $output = trim($process->output());
        $errorOut = trim($process->errorOutput());
        $exitCode = $process->exitCode();

        $suites = [];
        $calls = [];

        if (file_exists($callsFile)) {
            $calls = json_decode(file_get_contents($callsFile), true) ?? [];
            @unlink($callsFile);
        }

        if (file_exists($tmpFile)) {
            $xml = @simplexml_load_file($tmpFile);
            @unlink($tmpFile);
            if ($xml) {
                $suites = $this->parseSuites($xml, $calls);
            }
        }

        $total = collect($suites)->sum('tests');
        $failed = collect($suites)->sum(fn($s) => $s['failures'] + $s['errors']);

        return $this->respondWithJson([
            'suites' => $suites,
            'summary' => [
                'total' => $total,
                'passed' => $total - $failed,
                'failed' => $failed,
                'time' => round(collect($suites)->sum('time'), 3),
            ],
            'output' => $output ?: null,
            'error' => $errorOut ?: null,
            'exit_code' => $exitCode,
            'command' => implode(' ', array_map(fn($a) => str_contains($a, ' ') ? "\"{$a}\"" : $a, $args)),
        ]);
    }

    private function parseSuites(SimpleXMLElement $node, array $calls = []): array
    {
        $suites = [];

        foreach ($node->testsuite as $suite) {
            if (isset($suite->testcase)) {
                $suites[] = $this->parseSuite($suite, $calls);
            } else {
                $suites = array_merge($suites, $this->parseSuites($suite, $calls));
            }
        }

        return $suites;
    }

    private function parseSuite(SimpleXMLElement $suite, array $calls = []): array
    {
        $cases = [];
        $className = (string) $suite['name'];

        foreach ($suite->testcase as $tc) {
            $methodName = (string) $tc['name'];
            $status = 'passed';
            $message = null;

            if (isset($tc->failure)) {
                $status = 'failed';
                $message = trim((string) $tc->failure);
            } elseif (isset($tc->error)) {
                $status = 'error';
                $message = trim((string) $tc->error);
            } elseif (isset($tc->skipped)) {
                $status = 'skipped';
            }

            // Strip __HTTP_CALL__ from message (it's surfaced separately)
            $callData = null;
            if ($message && str_contains($message, '__HTTP_CALL__')) {
                $parts = explode('__HTTP_CALL__', $message, 2);
                $message = trim($parts[0]);
                try { $callData = json_decode(trim($parts[1]), true); } catch (\Throwable) {}
            }

            // Prefer calls file (covers passing tests too)
            $callKey = $className . '::' . $methodName;
            $callData = $calls[$callKey] ?? $callData;

            $cases[] = [
                'name' => $this->formatName($methodName),
                'status' => $status,
                'time' => round((float) $tc['time'], 3),
                'message' => $message,
                'http' => $callData,
            ];
        }

        return [
            'name' => $className,
            'tests' => (int) $suite['tests'],
            'failures' => (int) $suite['failures'],
            'errors' => (int) $suite['errors'],
            'time' => round((float) $suite['time'], 3),
            'cases' => $cases,
        ];
    }

    private function formatName(string $name): string
    {
        return ucfirst(str_replace('_', ' ', preg_replace('/^test_/', '', $name)));
    }
}
