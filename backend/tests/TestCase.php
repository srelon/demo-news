<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private static array $_lastRequest = [];

    public function json($method, $uri, array $data = [], array $headers = [], $options = []): \Illuminate\Testing\TestResponse
    {
        self::$_lastRequest = [
            'method' => strtoupper($method),
            'url' => $uri,
            'payload' => empty($data) ? null : $data,
            'status' => null,
            'response' => null,
        ];

        $response = parent::json($method, $uri, $data, $headers, $options);

        self::$_lastRequest['status'] = $response->status();
        self::$_lastRequest['response'] = json_decode($response->getContent(), true);

        return $response;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (!empty(self::$_lastRequest) && $file = getenv('PHPUNIT_CALLS_FILE')) {
            $key = static::class . '::' . $this->name();
            $calls = file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
            $calls[$key] = self::$_lastRequest;
            file_put_contents($file, json_encode($calls));
        }

        self::$_lastRequest = [];
    }

    public function onNotSuccessfulTest(\Throwable $t): never
    {
        if (!empty(self::$_lastRequest)) {
            $extra = "\n\n__HTTP_CALL__\n" . json_encode(self::$_lastRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            throw new \PHPUnit\Framework\AssertionFailedError($t->getMessage() . $extra, 0, $t);
        }

        parent::onNotSuccessfulTest($t);
    }
}
