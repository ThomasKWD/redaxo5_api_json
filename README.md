# JSON API addon for Redaxo 5

Redaxo 4.x addon to provide a read only JSON api for category and article content.

## Description

This addon works identical to the Redaxo 4.x version "redaxo4_api_json" except that it can only be installed under Redaxo 5.x.

Please read the [README](https://github.com/ThomasKWD/redaxo4_api_json/blob/master/README.md) there!


## Know issues

* In some situations the output may not work together with addons which use the "OUTPUT_FILTER" (e.g. "[SPROG](https://github.com/tbaddade/redaxo_sprog)". That means, SPROG's replacements may not be done for the json output.
