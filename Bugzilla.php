<?php

// -----------------------------------------------------------------------------
// Extension credits / metadata
// -----------------------------------------------------------------------------

$wgExtensionCredits['other'][] = array(
    'name'        => 'Bugzilla',
    'author'      => 'Christian Legnitto', 
    'url'         => 'https://github.com/LegNeato/mediawiki-bugzilla',
    'description' => 'This extension allows read-only integration with '.
                     'Bugzilla via the REST API',
);


// -----------------------------------------------------------------------------
// General setup
// -----------------------------------------------------------------------------

// Register the classes to autoload
$cwd = dirname(__FILE__); // We don't need to do this more than once!

$wgAutoloadClasses['Bugzilla']       = $cwd . '/Bugzilla.class.php';
$wgAutoloadClasses['BugzillaQuery']  = $cwd . '/BugzillaQuery.class.php';
$wgAutoloadClasses['BugzillaOutput'] = $cwd . '/BugzillaOutput.class.php';
$wgAutoloadClasses['BugzillaCacheI'] = $cwd . '/cache/BugzillaCacheI.class.php';
$wgAutoloadClasses['BugzillaCacheMysql'] = $cwd . '/cache/BugzillaCacheMysql.class.php';
$wgAutoloadClasses['BugzillaCacheDummy'] = $cwd . '/cache/BugzillaCacheDummy.class.php';

// -----------------------------------------------------------------------------
// Register our background job
// -----------------------------------------------------------------------------

$wgJobClasses['queryBugzillaUpdate']  = 'BugzillaUpdateJob';
$wgJobClasses['queryBugzillaInsert']  = 'BugzillaInsertJob';


// -----------------------------------------------------------------------------
// Register for MediaWiki hooks
// -----------------------------------------------------------------------------

$wgHooks['LoadExtensionSchemaUpdates'][] = 'BugzillaCreateCache';
$wgHooks['BeforePageDisplay'][]          = 'BugzillaIncludeHTML';
$wgHooks['ParserFirstCallInit'][]        = 'BugzillaParserInit';

// -----------------------------------------------------------------------------
// Hook work functions
// -----------------------------------------------------------------------------

// Schema updates for the database cache
function BugzillaCreateCache( $updater ) {
    if( $updater === null ) {
        // <= 1.16 support
        global $wgExtNewTables;
        global $wgExtModifiedFields;
        $wgExtNewTables[] = array(
            'bugzilla_cache',
            dirname( __FILE__ ) . '/cache.sql'
        );
    }else {
        // >= 1.17 support
        $updater->addExtensionUpdate( array( 'addTable',
                                             'bugzilla_cache',
                                             dirname( __FILE__ ) . '/cache.sql',
                                             TRUE )
        );
    }

    // Let the other hooks keep processing
    return TRUE;
}

// Add content to page HTML
function BugzillaIncludeHTML( &$out, &$sk ) {

    global $wgScriptPath;

    // Use remote jquery
    $out->addScript('<script type="text/javascript" src="$wgScriptPath/extensions/Bugzilla/web/jquery/1.6.2/jquery.min.js"></script>');

    // Use remote jquery ui
    $out->addScript('<script type="text/javascript" src="$wgScriptPath/extensions/Bugzilla/web/jqueryui/1.8.14/jquery-ui.min.js"></script>');

    // Add a local script file for the datatable
    $out->addScriptFile("$wgScriptPath/extensions/Bugzilla/web/js/jquery.dataTables.js" );

    // Add a remote jquery css file
    $out->addStyle("$wgScriptPath/extensions/Bugzilla/web/jqueryui/1.8.14/themes/base/jquery-ui.css");

    // Add a remote jquery UI theme css file
    $out->addStyle("$wgScriptPath/extensions/Bugzilla/web/jqueryui/1.8.14/themes/smoothness/jquery-ui.css");

    // Add local datatable styles
    $out->addStyle("$wgScriptPath/extensions/Bugzilla/web/css/demo_page.css");
    $out->addStyle("$wgScriptPath/extensions/Bugzilla/web/css/demo_table.css");

    // Add the script to do table magic
    $out->addInlineScript('$(document).ready(function() { 
                    $(".bugzilla").dataTable({
                                    "bJQueryUI": true
                     })});');

    // Let the other hooks keep processing
    return TRUE;
}

// Hook our callback function into the parser
function BugzillaParserInit( Parser &$parser ) {
    global $wgBugzillaTagName;

    // Register the desired tag
    $parser->setHook( $wgBugzillaTagName, 'BugzillaRender' );

    // Let the other hooks keep processing
    return TRUE;
}

// Function to be called when our tag is found by the parser
function BugzillaRender($input, array $args, Parser $parser, $frame ) {
    global $wgBugzillaRESTURL;

    // We don't want the page to be cached
    // TODO: Not sure if we need this
    $parser->disableCache();
    $input = $parser->recursiveTagParse($input, $frame);
    // Create a new bugzilla object
    $bz = Bugzilla::create($args, $input, $parser->getTitle());

    // Show the desired output (or an error if there was one)
    return $bz->render();
}


// -----------------------------------------------------------------------------
// Default configuration
// -----------------------------------------------------------------------------

$wgBugzillaRESTURL     = 'https://api-dev.bugzilla.mozilla.org/latest';
$wgBugzillaURL         = 'https://bugzilla.mozilla.org';
$wgBugzillaTagName     = 'bugzilla';
$wgBugzillaMethod      = 'REST'; // XML-RPC and JSON-RPC may be supported later
$wgBugzillaUseCache    = TRUE;
$wgBugzillaCacheMins   = 5;
$wgBugzillaJqueryTable = FALSE;

// Cache settings
$wgCacheObject = 'BugzillaCacheDummy';

$wgBugzillaChartStorage = realpath($cwd . '/charts');
$wgBugzillaFontStorage = $cwd . '/pchart/fonts';
$wgBugzillaChartUrl = $wgScriptPath . '/extensions/Bugzilla/charts';
