# Testing notes and caveats

removed call to local phpunit installation (was `phpunit.bat ./` which pointed to a phpunit-7.5.2.phar)

## Note!

To speed up tests and perform unit tests and integration at the same run I use a present Redaxo 4.7.2 installation reachable under http://localhost/tk/kwd-website. Hence some _tests cannot be run without this Redaxo_

Use [test.sh](./test.sh) for independent unit test (May fail because mock objects are incomplete).
Use [api_test.sh](./api_test.sh) for integration test (May fail because Redaxo not reachable under localhost OR not containing the expected content).

## Did you know?

Press Crtl+Shift+m to show preview of md file.
