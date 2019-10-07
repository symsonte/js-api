# Tests

## Init (first time)

cd code

composer update

cd test

docker-compose -f docker/all.yml up -d

docker exec -it symsonte_js_api_php sh

cd test
rm -rf var/cache/*
php bin/app.php /render-api

docker-compose -f docker/all.yml stop