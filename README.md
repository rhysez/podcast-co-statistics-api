## Design document for the Radio.co take home task


This design document has been written as part of my implementation of the take-home task given to me by Radio.co. I wanted to create this design document so that I could get my thoughts down in writing, explain my decisions and document any areas of improvement. At Quantavia, I will sometimes write design documents for certain features, so I thought it would be appropriate to do the same here.

For this task I’ve chosen to use Laravel as the backend framework due to my existing familiarity with it from my current role.

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
- **DownloadController**: Responsible for providing time series data for download records over time.
- **EventController**: Responsible for handling the requests associated with a podcast episode, such as the webhook and stats.
- **ProcessDownload**: Responsible for handling a queueable job where a PodcastEpisodeDownload record is created.
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

In `EventController`, the `webhook` method is responsible for validating the download data and storing it in the 
database in a format that can be easily queried in the future. 

In order to improve the response time of the endpoint, I made sure that the actual creation of the download record in the
database is handled in a queue using a job named `ProcessDownload`. As soon as the webhook validates 
the JSON, and also validates that the episode hasn't already been downloaded, the job is dispatched to the queue. This ensures
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

The request itself is handled by the `index` method on `DownloadController`. I'll step through this method and explain my 
thought process below.

```php
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

        // If the client doesn't provide start_date, we start from 7 days ago
        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
        } else {
            $startDate = Carbon::now()->subDays(7)->startOfDay();
        }

        // And if client doesn't provide end_date, we end on today
        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
        } else {
            $endDate = Carbon::now()->endOfDay();
        }
```
In the above code, the request is validated, and then the actual start/end dates that will be used to query the database 
are assigned conditionally based on the availability of the query parameters.

Both parameters are nullable, meaning that the frontend team can request without these parameters and get the last 7 days.
Alternatively, they can provide `start_date` and `end_date`, but not in isolation. The `start_date` also cannot be greater than 
`end_date`, and vice versa.

```php
        $downloadStats = Download::where('episode_id', $episodeId)
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

        return response()->json([
            'episode_id' => $episodeId,
            'range' => [
                'start' => $startDate?->toIso8601String(),
                'end'   => $endDate?->toIso8601String(),
            ],
            'data' => $tsData
        ]);
```
The downloads are queried for a specific episode, between the start and end date. To get the data that I needed for the time series structure,
I decided to select the `occurred_at` column and cast this to a new field named `date` because this would make more sense semantically to the frontend team, 
and create a new field named `download_count` which is derived from the total number of records. Finally, the data is grouped and sorted.

Due to time series data being generally represented as many points over some period of time, I needed to be able to iterate across each day between the specified 
start and end dates. To achieve this, I used `CarbonPeriod` to create an iterable collection of days between the start and end dates. 

Once I'd instantiated the `CarbonPeriod`, I just needed to iterate over it and push the new `date` and `download_count` data to a new array named `tsData` for each step in the loop.

Another motivation for using `CarbonPeriod` was so that the frontend team would have access to every day during that period, rather than only the days where the episode was downloaded. 
For these particular days, their `download_count` was assigned to zero (or `defaultValueForNoDownloads`).

### What I would do differently/improve if this were to be deployed to production

- I would construct the models, relationships, and tests required for a Podcast and Episode (alongside Download). This would make the API more maintainable, especially if podcast-specific or episode-specific features were to be added. It would allow us to aggregate data based on the relationships between podcasts, episodes and downloads, rather than purely downloads.
- I would improve the flexibility of the `start_date` and `end_date` query parameters to ensure that each parameter could be provided in isolation. 
- I would add code linting via Laravel Pint to ensure that the code is formatted/linted during CI/CD. I would also do the same for unit tests.
- I would consider adding pagination for large time series data sets. At the moment, there is nothing stopping somebody from requesting data for an extraordinarily long time period, and this could lead to long response times. To help reduce response times I would paginate the responses and allow the frontend team to control this pagination via query parameters like `start` and `limit`.
- If very large data sets were requested, I would probably try to cache this data using Redis. I would probably set a lifetime on the cached data so that it may be refreshed once the lifetime expires. This is really easy to do in Laravel because `Cache::get()` can take a closure which will query the database if the cache key doesn't exist.
- I would ensure that the API is sufficiently rate-limited. This is done by default in Laravel, however I would communicate with the frontend team to establish a sensible rate limit requirement.

