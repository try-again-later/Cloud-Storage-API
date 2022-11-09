# Cloud Storage API

## Postman

See the postman collection [here](https://github.com/try-again-later/Cloud-Storage-API/tree/master/postman).

## VPS

Deployed version is available at [194.226.121.94](http://194.226.121.94:80).

## How to start the service locally

```sh
git clone https://github.com/try-again-later/Cloud-Storage-API
cd Cloud-Storage-API

# https://laravel.com/docs/9.x/sail#installing-composer-dependencies-for-existing-projects
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php81-composer:latest \
    composer install --ignore-platform-reqs

./vendor/bin/sail up -d

./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh
```
