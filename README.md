MediaWiki extension for Bugzilla
================================

This is a MediaWiki extension that provides read-only access to the 
[Bugzilla REST API](https://wiki.mozilla.org/Bugzilla:REST_API) 

__Please note that this isn't finished / ready for production yet!__

Requirements
================================

* Requires HTTP_Request2 from PEAR
* Requires the SMARTY template library installed

Usage
================================

You use this extension in this way:

    <bugzilla>
        (JSON REST API query key/value pairs)
    </bugzilla>

An example:

    <bugzilla>
        {
            "product": "Bugzilla",
            "priority":"P1"
        }
    </bugzilla>

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

Limitations
================================

* This extension (by design) is read-only
* This extension currently queries as a public (not logged in) user
* Charts are fairly hardcoded and don't work in many cases

TODO
================================

1. This is basically a prototype right now...needs to be cleaned up a lot
2. The JQuery UI table doesn't render correctly...make it better
3. Support more types of queries than just "bug" (the default)
4. Support more types of wiki display than just a bug table
5. Caching and cache invalidation for queries
6. Support charting as a 1st class citizen
