<?php

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Http;
use Siberfx\CodelabStats\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

const API = 'https://analytics.code-labs.nl/api/v1';

/**
 * The decoded body of a mock API response stored in tests/Fixtures.
 *
 * @return array<string, mixed>
 */
function mockBody(string $name): array
{
    return json_decode(file_get_contents(__DIR__."/Fixtures/{$name}.json"), true, flags: JSON_THROW_ON_ERROR);
}

/** A fake HTTP response built from a mock fixture — tests never reach the real API. */
function mockResponse(string $name, int $status = 200): PromiseInterface
{
    return Http::response(mockBody($name), $status);
}
