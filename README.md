MediaWiki extension for Bugzilla
================================

This is a MediaWiki extension that provides read-only access to the 
[Bugzilla REST API](https://wiki.mozilla.org/Bugzilla:REST_API) 

__Please note that there are still big outstanding bug!__

Requirements
================================

* Requires HTTP_Request2 from PEAR
* Requires the SMARTY template library installed

Installation
================================

*These directions assume your MediaWiki installation is at /var/lib/mediawiki.
Please substitute your installation path if it is different*

1. Install the requirements above
2. Check the project out into `/var/lib/mediawiki/extensions/Bugzilla`
3. Edit `/etc/mediawiki/LocalSettings.php` and add
   `require_once("/var/lib/mediawiki/extensions/Bugzilla/Bugzilla.php");`
4. Edit `/etc/mediawiki/LocalSettings.php` and change/override any
configuration variables. Current configuration variables and their defaults
can be found at the end of `Bugzilla.php`
5. Run the MediaWiki update script to create the cache database table 
   `php /var/lib/mediawiki/maintenance/update.php`. *Note that you may need to
   add `$wgDBadminuser` and `$wgDBadminpassword` to 
   `/etc/mediawiki/LocalSettings.php` depending on your MediaWiki version

Usage
================================

You use this extension in this way:

    <bugzilla>
        (JSON REST API query key/value pairs)
    </bugzilla>

Examples:
All P1 bugs in the Bugzilla product:
    <bugzilla>
        {
            "product": "Bugzilla",
            "priority":"P1"
        }
    </bugzilla>

All new bugs flagged as uiwanted in the whiteboard:
    <bugzilla>
    	{
    	    "whiteboard": "uiwanted",
    	    "status": "NEW"
	}
    </bugzilla>

All bugs in the bugzilla.org component that were resolved in 2011:	
    <bugzilla>
	{
	    "component": "bugzilla.org",
	    "changed_after": "2011-01-01",
	    "changed_before": "2011-12-31",
	    "changed_field": "status",
	    "changed_field_to": "resolved"
	}
    </bugzilla>

Some commonly used query parameters are:
* id
* component* product* status* resolution* keywords
* whiteboard* target_milestone
* version
* changed_after
* changed_before

For more details on how to query in various ways, see the documentation for
the [Bugzilla REST API](https://wiki.mozilla.org/Bugzilla:REST_API)

Note that the tag name defaults to "bugzilla" but is configurable.

There is also __exploratory__ support for charting:

    <bugzilla type="count" display="bar">
        {
            "product":      "Bugzilla",
            "priority":     "P1",
            "x_axis_field": "severity"
        }
    </bugzilla>

Screenshot of the above:

![Screenshot of the above](http://i.imgur.com/1H868.png "Screenshot of the above")

Limitations
================================

* This extension (by design) is read-only
* This extension currently queries as a public (not logged in) user
* Charts are fairly hardcoded and don't work in many cases

Known Issues
================================
* There is currently no way to specify multiple values for a query parameter
* Large queries may exceed the allocated memory causing a blank page to be displayed. In this case you can recover by editing the page as follows:
If your wiki page has the URL 
https://wiki.mozilla.org/PagePath/PageTitle
The URL to edit your page is
https://wiki.mozilla.org/index.php?title=PagePath/PageTitle&action=edit

TODO
================================

1. The JQuery UI table doesn't render correctly...make it better
2. Support more types of queries than just "bug" (the default)
3. Support more types of wiki display than just a bug table
4. Caching and cache invalidation for queries
5. Support charting as a 1st class citizen
