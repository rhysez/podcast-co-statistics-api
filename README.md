### Design document for the Radio.co take home task



This design document has been written as part of my implementation of the take-home task given to me by Radio.co. I wanted to create this design document so that I could get my thoughts down in writing, explain my decisions and document any areas of improvement. At Quantavia, I will sometimes write design documents for certain features, so I thought it would be appropriate to do the same here.

For this task I’ve chosen to use Laravel as the backend framework due to my existing familiarity with it.



#### Containerisation

This API is containerised using Docker. There are multiple reasons why I would choose to containerise this API, especially in a production context:

    It effectively sandboxes the API so that it isn’t running directly on the host OS. In a team setting I think this is quite important, because it means that other developers can get up and running quickly using Docker Compose. 

    Dependencies are installed in the container, not directly on the host’s machine. This means that the host machine’s dependencies don’t conflict with that of the container, and the container only has the dependencies that it needs to run the API. 

    If this were being deployed to production and scaled up, we would get the benefit of container orchestration solutions like Kubernetes, which would allow us to automate the scaling up/down of services (pods) to fit the traffic. 



