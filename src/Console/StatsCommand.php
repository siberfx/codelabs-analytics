<?php

namespace Siberfx\CodelabStats\Console;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Siberfx\CodelabStats\CodelabStats;
use Siberfx\CodelabStats\Exceptions\MissingConfiguration;
use Siberfx\CodelabStats\Report;

class StatsCommand extends Command
{
    protected $signature = 'codelab-stats:stats
        {name : The report: browser, campaign, city, continent, country, device, event, language, operating_system, page, pageview, referrer, screen_resolution or visitor}
        {--from= : Start date (Y-m-d), defaults to 29 days before --to}
        {--to= : End date (Y-m-d), defaults to today}
        {--website= : Website ID, defaults to CODELAB_STATS_WEBSITE_ID}
        {--search= : Only rows whose value contains this text}
        {--sort-by=count : count or value}
        {--sort=desc : desc or asc}
        {--per-page=10 : 10, 25, 50 or 100}';

    protected $description = 'Show one CodeLabs Analytics report for a website';

    public function handle(CodelabStats $client): int
    {
        $to = $this->option('to') ?? Carbon::today()->toDateString();
        $input = [
            'name' => $this->argument('name'),
            'from' => $this->option('from') ?? Carbon::parse($to)->subDays(29)->toDateString(),
            'to' => $to,
            'sort_by' => $this->option('sort-by'),
            'sort' => $this->option('sort'),
            'per_page' => $this->option('per-page'),
        ];

        $validator = Validator::make($input, [
            'name' => ['required', Rule::enum(Report::class)],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'sort_by' => [Rule::in(Report::SORT_BY)],
            'sort' => [Rule::in(Report::SORT)],
            'per_page' => ['integer', Rule::in(Report::PER_PAGE)],
        ], [
            'name.enum' => 'Unknown report. Use one of: '.implode(', ', Report::names()).'.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $params = ['sort_by' => $input['sort_by'], 'sort' => $input['sort'], 'per_page' => (int) $input['per_page']];

        if (($search = $this->option('search')) !== null && $search !== '') {
            $params += ['search' => $search, 'search_by' => 'value'];
        }

        if ($website = $this->option('website')) {
            $client = $client->forWebsite($website);
        }

        try {
            $rows = $client->stats($input['name'], $input['from'], $input['to'], $params)['data'] ?? [];

            $this->components->info("{$input['name']} for website {$client->websiteId()}, {$input['from']} to {$input['to']}");
        } catch (MissingConfiguration $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        } catch (RequestException $e) {
            $this->components->error('CodeLabs Analytics answered '.$e->response->status().': '.json_encode($e->response->json('message')));

            return self::FAILURE;
        } catch (ConnectionException) {
            $this->components->error('CodeLabs Analytics could not be reached.');

            return self::FAILURE;
        }

        if ($rows === []) {
            $this->components->warn('No data for this range.');

            return self::SUCCESS;
        }

        $this->table(
            ['Value', 'Count'],
            array_map(fn (array $row) => [$row['value'] ?? '', $row['count'] ?? ''], $rows),
        );

        return self::SUCCESS;
    }
}
