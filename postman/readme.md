__Run "Auth/Get a CSRF token" request first__, this will automatically assign the CSRF token to the `CSRF_TOKEN`
collection variable. Otherwise, all other requests will just respond with 401.

Requests already include the correct IP address of the VPS ([194.226.121.94](http://194.226.121.94:80)) inside the
`BASE_URL` and `BASE_API_URL` collection variables.
