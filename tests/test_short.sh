#!/bin/bash

# ../vendor/bin/phpunit --bootstrap ../vendor/autoload.php --testdox ./kwd_jsonapi.Test.php
../vendor/bin/phpunit --bootstrap ../vendor/autoload.php --stop-on-error --stop-on-failure $1
