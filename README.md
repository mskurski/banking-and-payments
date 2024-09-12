# mskurski task

## Project setup
* Execute init script from console command `sh docker/init.sh` to setup config, composer install.
* SSH to container `docker compose exec php sh`

## Tests execute
* Unit: `php bin/phpunit -c . --testsuite unit`
