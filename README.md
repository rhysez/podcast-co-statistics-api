### Running the project and seeding the downloads table
1. The first step is to compose the containers using `sail build`. If you don't have the sail shell alias set up, run `./vendor/bin/sail build`. 
2. Start the containers using `sail up -d`.
3. Migrate your database and subsequently seed it using `sail artisan migrate:fresh --seed`. 
4. Run the tests using `sail test`. 
5. Start running the default queue using `sail artisan queue:work` (this is for the download processing job).

My `.env` is slightly different to `.env.example` as it contains the database credentials for MariaDB, which I chose to use for this task. If you need this during the interview, please let me know.

While working on this task, I used Postman to interface with the API and check response data.

If you want to make a webhook request in Postman to check the response, feel free to use this dummy request body:
```json
{
    "type": "episode.downloaded",
    "event_id": "550e8400-e29b-41d4-a716-446655440000",
    "occurred_at": "2026-01-24T18:50:00Z",
    "data": {
        "episode_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
        "podcast_id": "123e4567-e89b-12d3-a456-426614174000"
    }
}
```

If you want to request time series data, and you've seeded the database, you can make a GET request to this URL:
`localhost/api/episodes/88a0e4c0-0000-41d4-a716-446655440000/stats`

### Containerisation

This API is containerised using Docker. There are multiple reasons why I would choose to containerise this API, especially in a production context:

- It effectively sandboxes the API so that it isn’t running directly on the host OS. In a team setting I think this is quite important, because it means that other developers can get up and running quickly using Docker Compose. 

- Dependencies are installed in the container, not directly on the host’s machine. This means that the host machine’s dependencies don’t conflict with that of the container, and the container only has the dependencies that it needs to run the API. 

- If this were being deployed to production and scaled up, we would get the benefit of container orchestration solutions like Kubernetes, which would allow us to automate the scaling up/down of services (pods) to fit the traffic. 

### Notable classes

This is a list of the notable classes used in the system:

- **Download**: Responsible for representing a model record in the 'downloads' table.
- **DownloadController**: Responsible for providing the time series data for download records over time.
- **WebhookController**: Responsible for handling the webhook and dispatching jobs to queues based on the event type.
- **ProcessDownload**: Responsible for handling a queueable job where a Download record is created.
- Test classes are implemented in `tests/Feature/*`.
- Factory classes are implemented in `database/factories/*`
- Seeder classes are implemented in `database/seeders/*`

### Part 1: Storing download data with a webhook

This first part of this task requires a webhook that can be interfaced with via a POST request.
To ensure that this endpoint is maintainable for future extension, I created an enum which is
responsible for defining the potential event types. If a new event type needs to be added, it can be added as a variant to 
the enum. The event type of the request is checked using a switch statement.
```php
enum EventType: string {
    case EPISODE_DOWNLOADED = 'episode.downloaded';
}
```


```php
        switch ($request->type) {
            case EventType::EPISODE_DOWNLOADED->value:
                if (Download::where('event_id', $request->event_id)->exists()) {
                    return response()->json(['message' => 'Episode already downloaded'], 400);
                }
                ProcessDownload::dispatch($request->all());
                return response()->json(['message' => 'Webhook accepted'], 202);
            default:
                Log::info("Unknown event type found: {$request->type}");
                return response()->json(['message' => 'Unknown event type found.'], 422);
        }
```

In `WebhookController`, the `webhook` method is responsible for validating the download data and storing it in the 
database in a format that can be easily queried in the future. 

In order to improve the response time of the endpoint, I made sure that the actual creation of the download record in the
database is handled in a queue using a job named `ProcessDownload`. As soon as the webhook validates 
the JSON, and also validates that this exact download record doesn't already exist, the job is dispatched to the queue. This ensures
that the user only needs to wait for indication that the request was valid, rather than wait for the download record itself
to be created.


When the download record is stored, the `data` field is flattened so that it becomes two columns; `episode_id` and `podcast_id`. 
This is so that these two fields can be queried more easily than if they were nested as a JSON blob in one column.
```php
class ProcessDownload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $payload
    ) {}

    public function handle(): void
    {
        Download::create([
            'event_id'    => $this->payload['event_id'],
            'podcast_id'  => $this->payload['data']['podcast_id'],
            'episode_id'  => $this->payload['data']['episode_id'],
            'occurred_at' => $this->payload['occurred_at'],
        ]);
    }
}
```

### Part 2: Providing time-series data to the client

This part of the task requires implementation of a GET endpoint where the client can request time-series download data.

The time series data can be requested at `/api/episodes/{id}/stats`, with optional `start_date` and `end_date` query parameters.

The request itself is handled by the `statsForEpisode` method on `DownloadController`. I'll step through this method and explain my 
thought process below.

```php
        public function statsForEpisode(Request $request, string $episodeId): JsonResponse
    {
        $request->validate([
            'start_date' => [
                'bail',
                'nullable',
                'date',
                'required_with:end_date',
                'before_or_equal:end_date'
            ],
            'end_date' => [
                'nullable',
                'date',
                'required_with:start_date',
                'after_or_equal:start_date'
            ],
        ]);

        $statsService = new DownloadTimeSeriesService();

        $dates = $statsService->dateRange($request);

        $startDate = $dates['start_date'];
        $endDate = $dates['end_date'];

        $tsData = $statsService->aggregateByEpisode($episodeId, $startDate, $endDate);

        return response()->json([
            'episode_id' => $episodeId,
            'range' => [
                'start' => $startDate?->toIso8601String(),
                'end'   => $endDate?->toIso8601String(),
            ],
            'data' => $tsData
        ]);
    }
```
This method makes use of a service class named `DownloadTimeSeriesService` which handles the date range generation and time series data aggregation logic.
The benefit of this is that, because this logic is abstracted, we can re-use it elsewhere in other contexts. We could also extend the class to support other methods 
in the future, such as `aggregateByPodcast` if we wanted to get data based on podcasts rather than specific episodes.

```php
    private function aggregate($query, $startDate, $endDate): array
    {
        $downloadStats = $query
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->select([
                DB::raw('DATE(occurred_at) as date'),
                DB::raw('COUNT(*) as download_count')
            ])
            ->groupBy('date')
            ->orderBy('occurred_at', 'ASC')
            ->get();

        $downloadCountsByDate = $downloadStats->pluck('download_count', 'date');
        $tsData = [];
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        $defaultValueForNoDownloads = 0;

        foreach ($period as $date) {
            $dbDate = $date->format('Y-m-d');
            $displayDate = $date->format('d-m-Y');
            $tsData[] = [
                'date'  => $displayDate,
                'download_count' => $downloadCountsByDate->get($dbDate, $defaultValueForNoDownloads),
            ];
        }

        return $tsData;
    }
```
The actual time series aggregation is handled by the above method in `DownloadTimeSeriesService`. The base query is decided by the `$query` parameter, 
which gives us the flexibility to aggregate other data in the future. The rest of the query handles the sorting of the data and casting of columns so that 
we can make use of `date` and `download_count` which are key to the time series data.

The `date` and `download_count` fields are plucked so that we can use them later. I decided to use `CarbonPeriod` to create an iterable list of dates between 
the date range specified, and I'm using a foreach loop to iterate over this and push the data to the `tsData` array.

