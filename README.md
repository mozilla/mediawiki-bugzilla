MediaWiki extension for Bugzilla
================================

This is a MediaWiki extension that provides read-only access to the 
[Bugzilla REST API](https://wiki.mozilla.org/Bugzilla:REST_API) 

__Please note that there are still big outstanding bugs!__

Requirements
================================

* Requires <a href="http://pear.php.net/package/HTTP_Request2">HTTP_Request2 from PEAR</a>
* For charting, requires <a href="http://libgd.bitbucket.org/">gd</a>

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
   `php /var/lib/mediawiki/maintenance/update.php`. __Note that you may need to
   add `$wgDBadminuser` and `$wgDBadminpassword` to 
   `/etc/mediawiki/LocalSettings.php` depending on your MediaWiki version__

Usage
================================

You use this extension in this way:

    <bugzilla>
        (JSON REST API query key/value pairs)
    </bugzilla>

By default, it will output a colored table:

![Example output](http://i.imgur.com/IM6xd.png"Example output")

Note that the wiki tag name defaults to "bugzilla" but is 
configurable by the administrator.

Options
================================

Valid bugzilla tag options are:

* type: ``"bug"`` or ``"count"`` (defaults to bug)
* For type bug:
    * display: ``"table"`` or ``"list"`` (defaults to table)
* For type count:
    * display: ``"bar"`` or ``"pie"``
    * size: ``"small"``, ``medium"`` or ``"large"`` (defaults to large)
* stats: ``"show"`` or ``"hide"`` (defaults to "show")


Examples
================================

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

All bugs in the bugzilla.org component that were resolved in 2011,
with the stats summary hidden:	

    <bugzilla stats="hide">
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
* component
* product
* status
* resolution
* keywords
* whiteboard
* target_milestone
* version
* changed_after
* changed_before

For more details on how to query in various ways, see the documentation for
the [Bugzilla REST API](https://wiki.mozilla.org/Bugzilla:REST_API)


Configurable fields/columns
================================

Specify fields in the "include_fields" setting of BZ REST API options as you 
normally would. Mediawiki-bugzilla will then a) only fetch those fields 
and b) display those columns.

    <bugzilla>
    {
        "whiteboard": "[mediawiki-bugzilla]",
        "include_fields": "id, summary, whiteboard, status, resolution"
    }
    </bugzilla>

![Screenshot of the above](http://i.imgur.com/p3u7r.png "Screenshot of the above")


Charting
================================

There is also _some_ support for charting:

    <bugzilla type="count" display="bar">
        {
            "whiteboard": "[snappy:p1]",
            "x_axis_field": "status"
        }
    </bugzilla>

Screenshot of the above:

![Screenshot of the above](http://i.imgur.com/tDUZ1.png "Screenshot of the above")

    <bugzilla type="count" display="pie">
    {
        "whiteboard": "[mediawiki-bugzilla]",
        "x_axis_field": "status"
    }
    </bugzilla>
    <bugzilla type="count" display="pie" size="medium">
    {
        "whiteboard": "[mediawiki-bugzilla]",
        "x_axis_field": "status"
    }
    </bugzilla>
    <bugzilla type="count" display="pie" size="small">
    {
        "whiteboard": "[mediawiki-bugzilla]",
        "x_axis_field": "status"
    }
    </bugzilla>

Screenshot of the above:

![Screenshot of the above](http://i.imgur.com/mobHA.png "Screenshot of the above")


Limitations
================================

* This extension (by design) is read-only
* This extension currently queries as a public (not logged in) user
* Charts are fairly hardcoded and don't work in many cases

Known Issues
================================
* The __size__ attribute only works on pie charts
* Rendering a page with an uncached query can take a bit
* Large queries may exceed the allocated memory causing a blank page to be displayed. In this case you can recover by editing the page as follows:
If your wiki page has the URL 
    https://wiki.mozilla.org/PagePath/PageTitle
The URL to edit your page is 
    https://wiki.mozilla.org/index.php?title=PagePath/PageTitle&action=edit

TODO
================================
* Add more/smarter field display templates
