# Tests

## Init (first time)

cd js-api

docker run --rm --interactive --tty --volume $PWD:/app composer install --no-interaction --prefer-dist --ignore-platform-reqs --no-scripts

docker-compose -f docker/docker-compose.yml up -d

cp config/parameters.dist.yml config/parameters.yml

chmod a+w var/cache


