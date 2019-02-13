# json API addon for Redaxo 5.x

## Abstract

[Redaxo 5.x](https://redaxo.org) addon to provide a _read only_ JSON api for "category" and "article" content.

## Requirements

### Software

* Redaxo 5.x installation (MySQL 5.x, PHP 7.1)
* Mod Rewrite access (optional).

### Configuration

The addon uses 3 fields of the PHP superglobal `$_SERVER` which must be existent and have the proper content.
Although under a default apache + php configuration these should not be a problem - you should know about it:

`$_SERVER['REQUEST_METHOD']` containing the http method e.g. 'GET', case insensitive -- this var is used indirectly by calling the Redaxo function "rex_request_method()"

`$_SERVER['REQUEST_SCHEME']` containing the protocol ("http" or "https")

`$_SERVER['QUERY_STRING']` containing the query part of the URI -- everything from the "?" _after rewrite rule transformation_ (e. g. "api=categories/4")

## Installation

1. *Copy* all files into  a sub directory "*api_json*" under `redaxo/src/addons/` of your Redaxo 5.x installation. Then start "Install" on the "Addons" page in the backend.

2. Optional: Add a *rewrite rule* to your ".htaccess" file or apache config. Just convert all links starting with 'api/' to a param e. g.: `RewriteRule ^api[/]?(.*)$ index.php?api=$1`. if you like to use the "hierarchical syntax".

## Usage

You can easily get titles or full body contents of 1 level of sub categories. You specify a valid category ID or get root categories.

You also can request rendered article content by appending `/articles/contents`. This explicit keyword is for  minimizing respond data load because often just a list of article titles or links are needed.

By adding a number after `/contents/` you select a ctype ID. Otherwise always ctype 1 is used.

Just try your Redaxo project URI with `/api`. It provides an entry point and suggestions. More usage examples and explanations can be found in the response itself.

### Syntax Examples:

`yourdomain.tld/api` entry point, currently provides root categories.

`yourdomain.tld/api/categories/1` returns category and its sub categories ("1" must be the id of an existing categoriy with status "online").

`yourdomain.tld/api/categories/3/articles` returns category with ID == 3 and all articles in it and in its sub categories.

`yourdomain.tld/api/categories/3/articles/contents/2` returns "article content" of ctype 2 of all articles found.

`yourdomain.tld/api/articles/48/contents` returns a single "article".



You can use the api without a rewrite rule. Type e.g. `yourdomain.tld/index.php?api=categories/4`.

## Response

Always returns a body in JSON. On HTTP erros the body contains explanations.

Assuming public content header `Access-Control-Allow-Origin: *` is always sent.

The JSON resembles the category structure of [Redaxo](https://redaxo.org). But it can only return 1 category level and its immediate sub categories in 1 response.

If requested by categories only categories and sub articles with status "online" are returned. If requestied a single article, "offline" articles are also returned. (The response contains a field "status" for each article with value `1` for "online" and `0` for "offline".)

Currently _all_ "metainfo" data available is included.

Some fields are redundant, but provide better understanding esp. when a client parses categories and articles separately.

Example response made from "https://www.kuehne-webdienste.de/api/categories/3/0/articles":

```
{
  request: "api/categories/3/0/articles",
  id: 3,
  clang: 0,
  pid: 3,
  re_id: 0,
  name: "Referenzen",
  prior: 1,
  attributes: "",
  startpage: 1,
  path: "|",
  createdate: 1280159918,
  updatedate: 1544285487,
  template_id: 1,
  createuser: "Thomas",
  updateuser: "Thomas",
  revision: 0,
  link: "https://www.kuehne-webdienste.de/api/categories/3/0",
  categories: [
    {
      id: 12,
      clang: 0,
      pid: 12,
      re_id: 3,
      name: "Shuri Ryu Berlin",
      prior: 1,
      attributes: "",
      startpage: 1,
      path: "|3|",
      createdate: 1404049179,
      updatedate: 1543838561,
      template_id: 13,
      createuser: "Thomas",
      updateuser: "Thomas",
      revision: 0,
      link: "https://www.kuehne-webdienste.de/api/categories/12/0/articles",
      articles: [
        {
          id: 12,
          clang: 0,
          pid: 12,
          re_id: 12,
          name: "Shuri Ryu Berlin",
          catname: "Shuri Ryu Berlin",
          attributes: "",
          startpage: 1,
          prior: 1,
          path: "|3|",
          createdate: 1404049179,
          updatedate: 1543838561,
          template_id: 13,
          createuser: "Thomas",
          updateuser: "Thomas",
          revision: 0,
          metainfos: {
            art_online_from: "",
            art_online_to: "",
            art_description: "",
            art_keywords: "",
            art_file: "",
            art_teaser: "",
            art_type_id: "",
            art_showtitle: "normal"
          }
        }
      ]
    },
    {
      id: 7,
      clang: 0,
      pid: 7,
      re_id: 3,
      name: "Tangará Brasil",
      prior: 1,
      attributes: "",
      startpage: 1,
      path: "|3|",
      createdate: 1280159902,
      updatedate: 1486048701,
      template_id: 13,
      createuser: "Thomas",
      updateuser: "Thomas",
      revision: 0,
      link: "https://www.kuehne-webdienste.de/api/categories/7/0/articles",
      articles: [
        {
          id: 7,
          clang: 0,
          pid: 7,
          re_id: 7,
          name: "Tangará Brasil",
          catname: "Tangará Brasil",
          attributes: "",
          startpage: 1,
          prior: 1,
          path: "|3|",
          createdate: 1280159902,
          updatedate: 1486048701,
          template_id: 13,
          createuser: "Thomas",
          updateuser: "Thomas",
          revision: 0,
          metainfos: {
            art_online_from: "",
            art_online_to: "",
            art_description: "",
            art_keywords: "",
            art_file: "",
            art_teaser: "",
            art_type_id: "",
            art_showtitle: "normal"
          }
        }
      ]
    },
    {
      id: 13,
      clang: 0,
      pid: 13,
      re_id: 3,
      name: "Moldt Events",
      prior: 1,
      attributes: "",
      startpage: 1,
      path: "|3|",
      createdate: 1404049185,
      updatedate: 1410461900,
      template_id: 13,
      createuser: "Thomas",
      updateuser: "Thomas",
      revision: 0,
      link: "https://www.kuehne-webdienste.de/api/categories/13/0/articles",
      articles: [
        {
          id: 13,
          clang: 0,
          pid: 13,
          re_id: 13,
          name: "Moldt Events",
          catname: "Moldt Events",
          attributes: "",
          startpage: 1,
          prior: 1,
          path: "|3|",
          createdate: 1404049185,
          updatedate: 1410461900,
          template_id: 13,
          createuser: "Thomas",
          updateuser: "Thomas",
          revision: 0,
          metainfos: {
            art_online_from: "",
            art_online_to: "",
            art_description: "",
            art_keywords: "",
            art_file: "",
            art_teaser: "",
            art_type_id: "",
            art_showtitle: "normal"
          }
        }
      ]
    }
  ],
  articles: [
    {
      id: 3,
      clang: 0,
      pid: 3,
      re_id: 3,
      name: "Referenzen, Auswahl",
      catname: "Referenzen",
      attributes: "",
      startpage: 1,
      prior: 1,
      path: "|",
      createdate: 1280159918,
      updatedate: 1544285487,
      template_id: 1,
      createuser: "Thomas",
      updateuser: "Thomas",
      revision: 0,
      metainfos: {
        art_online_from: "",
        art_online_to: "",
        art_description: "",
        art_keywords: "",
        art_file: "",
        art_teaser: "",
        art_type_id: "",
        art_showtitle: "normal"
      }
    }
  ]
}
```

Note: This example has been copied from an formatter for better readability. The actual response **has** quoted field names and escaped slashes.


# JSON API addon for Redaxo 5

Redaxo **5.x** addon to provide a read only JSON api for category and article content.

## Description

This addon works identical to the Redaxo 4.x version "redaxo4_api_json" except that it can only be installed under Redaxo 5.x.

Please read the [README there](https://github.com/ThomasKWD/redaxo4_api_json/blob/master/README.md)!

## Requirements

### Software

* PHP 7.1
* Redaxo 5.x (Only tested with Redaxo 5.6.0)
* Mod Rewrite access (optional).

## Know issues

* In some situations the output may not work together with addons which use the "OUTPUT_FILTER" (e.g. "[SPROG](https://github.com/tbaddade/redaxo_sprog)". That means, SPROG's replacements may not be done for the json output.
