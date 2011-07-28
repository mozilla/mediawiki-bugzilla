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
$wgAutoloadClasses['Bugzilla'] = dirname(__FILE__) . '/Bugzilla.class.php';

// -----------------------------------------------------------------------------
// Register for MediaWiki hooks
// -----------------------------------------------------------------------------

$wgHooks['BeforePageDisplay'][]   = 'BugzillaIncludeHTML';
$wgHooks['ParserFirstCallInit'][] = 'BugzillaParserInit';

// -----------------------------------------------------------------------------
// Hook work functions
// -----------------------------------------------------------------------------

function BugzillaIncludeHTML( &$out, &$sk ) {

    global $wgScriptPath;

    // Use remote jquery
    $out->addScript('<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>');

    // Use remote jquery ui
    $out->addScript('<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/jquery-ui.min.js"></script>');

    // Add a local script file for the datatable
    $out->addScriptFile("$wgScriptPath/extensions/Bugzilla/web/js/jquery.dataTables.js" );

    // Add a remote jquery css file
    $out->addStyle("http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/themes/base/jquery-ui.css");

    // Add a remote jquery UI theme css file
    $out->addStyle("http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/themes/smoothness/jquery-ui.css");

    // Add local datatable styles
    $out->addStyle("$wgScriptPath/extensions/Bugzilla/web/css/demo_page.css");
    $out->addStyle("$wgScriptPath/extensions/Bugzilla/web/css/demo_table.css");

    // Add the script to do table magic
    $out->addInlineScript('$(document).ready(function() { 
                    $(".bugzilla").dataTable({
                                    "bJQueryUI": true,
                              "sPaginationType": "full_numbers"
                     })});');

    // Let the other hooks keep processing
    return true;
}

// Hook our callback function into the parser
function BugzillaParserInit( Parser &$parser ) {
    global $wgBugzillaTagName;

    // Register the desired tag
    $parser->setHook( $wgBugzillaTagName, 'BugzillaRender' );

    // Let the other hooks keep processing
    return true;
}

// Function to be called when our tag is found by the parser
function BugzillaRender($input, array $args, Parser $parser ) {
    global $wgBugzillaRESTURL;

    // We don't want the page to be cached
    // TODO: Not sure if we need this
    $parser->disableCache();

    // Create a new bugzilla object
    $bz = new Bugzilla($wgBugzillaRESTURL, $args);

    // Talk to bugzilla
    $bz->fetch($input);

    // Show the results (or an error if there was one)
    return $bz->render();
}


// -----------------------------------------------------------------------------
// Default configuration
// -----------------------------------------------------------------------------

$wgBugzillaRESTURL     = 'https://api-dev.bugzilla.mozilla.org/latest';
$wgBugzillaURL         = 'https://bugzilla.mozilla.org';
$wgBugzillaTagName     = 'bugzilla';
$wgBugzillaUseCache    = TRUE;
$wgBugzillaCacheMins   = 5;
$wgBugzillaJqueryTable = FALSE;

// We use smarty...
$wgBugzillaSmartyDir         = '/usr/share/php/smarty/';
$wgBugzillaSmartyTemplateDir = dirname(__FILE__) . '/templates/';
$wgBugzillaSmartyCompileDir  = '/tmp/';
$wgBugzillaSmartyConfigDir   = dirname(__FILE__) . '/configs/';
$wgBugzillaSmartyCacheDir    = '/tmp/';

?>
