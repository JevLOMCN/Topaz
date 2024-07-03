Shellbox
========

Shellbox is a library and server for containerized shell execution.

More information on how to set up and configure Shellbox is available
at <https://www.mediawiki.org/wiki/Shellbox>.

## Set up your dev environment
Granted you have docker-compose installed, and that you can issue docker commands as your user, you can have 
a working development setup by running

    $ make run

this will build an appropriate container for your application (if not present) and run the whole
httpd/php-fpm combo for you, and listen on port 8080. It will use your local source as a volume, so you will be able to see changes in the code reflected in responses from the daemon instantly.

If you change the dependencies, and thus composer.json or composer.lock, you will have to force a rebuild of the container, by using 

    $ make rebuild

To run the tests that run in CI locally (also via docker), you need can use `make test`.
