# Testing notes and caveats

removed call to local phpunit installation (was `phpunit.bat ./` which pointed to a phpunit-7.5.2.phar)

## Note!

Integration tests are done in docker image composement against the installation of the default "Redaxo Demo v."

Use [test.sh](./test.sh) for independent unit test (May fail because mock objects are incomplete).
Use [api_test.sh](./api_test.sh) for integration test (May fail because Redaxo not reachable under localhost OR not containing the expected content).

## Docker integration

Using the docker images from https://github.com/FriendsOfREDAXO/redaxo-mit-docker

### Known issues

"Composer" is not installed in the docker image. You must add it and build a new image "redaxo-docker_web" or use a local installation.

I currently use a modified container image. Workdir: project root, downloaded compose.phar, executed `php composer.phar install`. Seems to work with php version found in docker image.
