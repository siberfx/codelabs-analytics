<?php

namespace Siberfx\CodelabStats\Http\Controllers;

use Closure;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Siberfx\CodelabStats\CodelabStats;

/**
 * Read-only JSON endpoints for a dashboard. API errors are passed through
 * with their original status so the front end can show them.
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

        return $this->proxy(fn () => $client->summary($range['from'], $range['to']));
    }

    public function stats(Request $request, CodelabStats $client): JsonResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return $this->proxy(fn () => $client->stats(
            $input['name'],
            $input['from'],
            $input['to'],
            $request->only(self::FORWARDED),
        ));
    }

    /** @param  Closure(): array<string, mixed>  $call */
    private function proxy(Closure $call): JsonResponse
    {
        try {
            return response()->json($call());
        } catch (RequestException $e) {
            return response()->json(
                $e->response->json() ?? ['message' => 'CodeLabs Analytics request failed.'],
                $e->response->status(),
            );
        }
    }
}
