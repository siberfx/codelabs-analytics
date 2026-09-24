<?php

namespace Siberfx\CodelabStats\Http\Controllers;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Siberfx\CodelabStats\CodelabStats;
use Siberfx\CodelabStats\Exceptions\MissingConfiguration;
use Siberfx\CodelabStats\Report;

/**
 * Read-only JSON endpoints for a dashboard.
 *
 * A failure on the CodeLabs side is answered with 502 (503 when the package
 * is not configured), never with the API's own status: a 401 or 404 from
 * CodeLabs would otherwise read as "your session expired" or "this route
 * does not exist" to the front end calling these routes.
 */
class StatsController extends Controller
{
    /** Query parameters forwarded to the stats endpoint besides name/from/to. */
    private const array FORWARDED = ['search', 'search_by', 'sort_by', 'sort', 'per_page', 'page'];

    public function summary(Request $request, CodelabStats $client): JsonResponse
    {
        $range = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return $this->proxy($client, fn () => $client->summary($range['from'], $range['to']));
    }

    public function stats(Request $request, CodelabStats $client): JsonResponse
    {
        // Mirrors the API's own rules, so bad input is a local 422 rather than a 502 from CodeLabs.
        $input = $request->validate([
            'name' => ['required', Rule::enum(Report::class)],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'search' => ['sometimes', 'string', 'max:255'],
            'search_by' => ['sometimes', Rule::in(Report::SEARCH_BY)],
            'sort_by' => ['sometimes', Rule::in(Report::SORT_BY)],
            'sort' => ['sometimes', Rule::in(Report::SORT)],
            'per_page' => ['sometimes', 'integer', Rule::in(Report::PER_PAGE)],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        return $this->proxy($client, fn () => $client->stats(
            $input['name'],
            $input['from'],
            $input['to'],
            Arr::only($input, self::FORWARDED),
        ));
    }

    /** @param  Closure(): array<string, mixed>  $call */
    private function proxy(CodelabStats $client, Closure $call): JsonResponse
    {
        try {
            return response()->json($call());
        } catch (MissingConfiguration $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        } catch (RequestException $e) {
            return response()->json([
                'message' => $this->describe($client, $e),
                'upstream_status' => $e->response->status(),
                'upstream' => $e->response->json(),
            ], 502);
        } catch (ConnectionException) {
            return response()->json(['message' => 'CodeLabs Analytics could not be reached.'], 502);
        }
    }

    private function describe(CodelabStats $client, RequestException $e): string
    {
        return match ($e->response->status()) {
            401, 403 => 'CodeLabs Analytics rejected the API key.',
            404 => "CodeLabs Analytics has no website {$client->websiteId()} for this API key.",
            422 => 'CodeLabs Analytics rejected the request: '.$this->firstMessage($e->response->json('message')),
            default => 'CodeLabs Analytics answered with status '.$e->response->status().'.',
        };
    }

    /** The API sends either a string or a Laravel-style field => [messages] map. */
    private function firstMessage(mixed $message): string
    {
        if (is_array($message)) {
            $message = collect($message)->flatten()->first();
        }

        return is_string($message) && $message !== '' ? $message : 'invalid parameters.';
    }
}
