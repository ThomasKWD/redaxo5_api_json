#!/bin/bash

../vendor/bin/phpunit --bootstrap ../vendor/autoload.php --testdox ./kwd_jsonapi.Test.php
../vendor/bin/phpunit --bootstrap ../vendor/autoload.php ./kwd_jsonapi.Test.php
