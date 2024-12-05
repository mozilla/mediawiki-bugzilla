MediaWiki extension for Bugzilla
================================

This is a MediaWiki extension that provides read-only access to the 
[Bugzilla REST API](https://wiki.mozilla.org/Bugzilla:REST_API) 

__Please note that there are still big outstanding bugs!__

Requirements
================================

* MediaWiki 1.17 or above.
* For charting, requires <a href="http://libgd.bitbucket.org/">gd</a>

Installation
================================

*These directions assume your MediaWiki installation is at /var/lib/mediawiki.
Please substitute your installation path if it is different*

1. Install the requirements above
2. Check the project out into `/path/to/your/mediawiki/extensions/Bugzilla`
3. Edit `/path/to/your/mediawiki/LocalSettings.php` and add
   `require_once("$IP/extensions/Bugzilla/Bugzilla.php");`
   and change/override any configuration variables.
   Current configuration variables and their defaults can be found at the end of `Bugzilla.php`

Usage
================================

You use this extension in this way:

    <bugzilla>
        (JSON REST API query key/value pairs)
    </bugzilla>

By default, it will output a colored table:

![Example output](http://i.imgur.com/IM6xd.png)

Note that the wiki tag name defaults to "bugzilla" but is 
configurable by the administrator.

Options
================================

Valid bugzilla tag options are:

* type: ``"bug"`` or ``"count"`` (defaults to bug)
* For type bug:
    * display: ``"table"`` or ``"list"`` or `"count"` (defaults to table)
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
        "include_fields": ["id", "summary", "whiteboard", "status", "resolution"]
    }
    </bugzilla>

![Screenshot of the above](http://i.imgur.com/p3u7r.png "Screenshot of the above")

Limitations
================================

* This extension (by design) is read-only
* This extension currently queries as a public (not logged in) user

Known Issues
================================
* Rendering a page with an uncached query can take a bit
* Large queries may exceed the allocated memory causing a blank page to be displayed. In this case you can recover by editing the page as follows:
If your wiki page has the URL 
    https://wiki.mozilla.org/PagePath/PageTitle
The URL to edit your page is 
    https://wiki.mozilla.org/index.php?title=PagePath/PageTitle&action=edit
