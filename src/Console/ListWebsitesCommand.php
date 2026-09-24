<?php

namespace Siberfx\CodelabStats\Console;

use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Siberfx\CodelabStats\CodelabStats;
use Siberfx\CodelabStats\Exceptions\MissingConfiguration;

class ListWebsitesCommand extends Command
{
    protected $signature = 'codelab-stats:websites {--search= : Only show domains containing this text}';

    protected $description = 'List the CodeLabs Analytics websites (and their IDs) your API key can read';

    public function handle(CodelabStats $client): int
    {
        $params = ['per_page' => 100];

        if ($search = $this->option('search')) {
            $params += ['search' => $search, 'search_by' => 'domain'];
        }

        try {
            $sites = $client->websites($params)['data'] ?? [];
        } catch (MissingConfiguration $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        } catch (RequestException $e) {
            $this->components->error('CodeLabs Analytics answered '.$e->response->status().': '.json_encode($e->response->json('message')));

            return self::FAILURE;
        }

        if ($sites === []) {
            $this->components->warn('No websites found for this API key.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Domain', 'Created'],
            array_map(fn (array $site) => [$site['id'] ?? '', $site['domain'] ?? '', $site['created_at'] ?? ''], $sites),
        );

        return self::SUCCESS;
    }
}
