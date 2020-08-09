# WPMultiplane

WordPress plugin, that integrates [Cockpit CMS][1] and [CpMultiplane][2].

This is **very experimental** and not fully implemented. Feedback and pull requests are very welcome.


## Requirements

PHP 7+

Permalinks **must not be 'plain'**
Go to "Settings" --> "Permalinks" and change the radio button from "Plain" to "Post name" or something else. Otherwise the generated `.htaccess` file doesn't rewrite urls and url bindings won't work.

Also the `.htaccess` file must be changed to deliver `.tag` files as javascript. See `templates/.htaccess.dist`.

## changes in libs

* `lib/cockpit/bootstrap.php`: `$app = new LimeExtra\App($config);` --> `$app = new LimeWordPress\App($config);`

## Problems

WordPress has no output buffer, which can cause problems with sessions. I created a simple output buffer in the `run()` method

Priorities in `add_action` hooks are in reverse order, than the priority of Cockpit events. No big problem, but confusing.

```php
add_action('init', function() {}, 0);           // fires early
add_action('init', function() {}, 100);         // fires late
cockpit()->on('admin.init', function() {}, 0)   // fires late
cockpit()->on('admin.init', function() {}, 100) // fires early
```

## Global scope polution

New functions in global scope:

* `cockpit()`
* `mp()`
* to do... `print_r(get_defined_functions()['user']);`

Most things are namespaced or realized with anonymous functions.

## Recommended WP addons

Must have:

* disable-google-fonts
* disable-emojis
* disable-embeds

Eventually:

* disable-wp-rest-api
* disable-blog
* disable-comments

Simple, useful, not (very) bloated:

* redirection
* header-and-footer-scripts
* the-seo-framework

## to do

* [ ] mp.js enqueue script properly (currently in Privacy module)
* [ ] rlj-blocks: functions should be in class to avoid global scope polution
* [ ] shortcodes for collections, mp partials...
* [ ] cleanup
* [ ] 
* [ ] 

## build

install/update dependencies:

`composer install --no-dev --ignore-platform-reqs`
`composer update --no-dev --ignore-platform-reqs`

## Copyright and License

Copyright 2019 Raffael Jesche under the MIT license.

See [LICENSE][5] for more information.

## Credits and third party resources

### libraries

* [Cockpit CMS][1] version 0.11.0 from [Artur Heinze][4], license: MIT
* [CpMultiplane][2] version 0.2.2 from [Raffael Jesche][6], license: MIT

### modules

* [SqlDriver][3] from [Piotr Konieczny][4], license: MIT


[1]: https://github.com/agentejo/cockpit
[2]: https://github.com/raffaelj/CpMultiplane
[3]: https://github.com/piotr-cz/cockpit-sql-driver
[4]: https://github.com/aheinze
[5]: https://github.com/raffaelj/WPMultiplane/blob/master/LICENSE
[6]: https://www.rlj.me
