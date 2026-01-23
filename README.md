### Design document for the Radio.co take home task



This design document has been written as part of my implementation of the take-home task given to me by Radio.co. I wanted to create this design document so that I could get my thoughts down in writing, explain my decisions and document any areas of improvement. At Quantavia, I will sometimes write design documents for certain features, so I thought it would be appropriate to do the same here.

For this task I’ve chosen to use Laravel as the backend framework due to my existing familiarity with it.



#### Containerisation

This API is containerised using Docker. There are multiple reasons why I would choose to containerise this API, especially in a production context:

- It effectively sandboxes the API so that it isn’t running directly on the host OS. In a team setting I think this is quite important, because it means that other developers can get up and running quickly using Docker Compose. 

- Dependencies are installed in the container, not directly on the host’s machine. This means that the host machine’s dependencies don’t conflict with that of the container, and the container only has the dependencies that it needs to run the API. 

- If this were being deployed to production and scaled up, we would get the benefit of container orchestration solutions like Kubernetes, which would allow us to automate the scaling up/down of services (pods) to fit the traffic. 

#### Part 1: Storing download data with a webhook

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
                ProcessPodcastEpisodeDownload::dispatch($request->all());
                return response()->json(['message' => 'Webhook accepted'], 202);
            default:
                Log::info("Unknown event type found: {$request->type}");
                return response()->json(['message' => 'Unknown event type found.'], 422);
        }
```

In `PodcastEpisodeController`, the `webhook` method is responsible for validating the download data and storing it in the 
database in a format that can be easily queried in the future. 

In order to improve the response time of the endpoint, I made sure that the actual creation of the download record in the
database is handled in a Redis queue using a job named `ProcessPodcastEpisodeDownload`. As soon as the webhook validates 
the JSON, and also validates that the episode hasn't already been downloaded, the job is dispatched to the queue. This ensures
that the user only needs to wait for indication that the request was valid, rather than wait for the download record itself
to be created.


When the download record is stored, the `data` field is flattened so that it becomes two columns; `episode_id` and `podcast_id`. 
This is so that these two fields can be queried more easily than if they were nested as a JSON blob in one column.
```php
class ProcessPodcastEpisodeDownload implements ShouldQueue
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
