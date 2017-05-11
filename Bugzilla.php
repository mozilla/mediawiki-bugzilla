<?php
# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.

/**
 * This is the configuration file for mediawiki-bugzilla. It contains important
 * settings that should be reviewed and customized for your environment. Please
 * see the instructions on each line for details about what should be
 * customized and how to properly install the application.
 *
 * For maximum commpatibility with Mediawiki, settings modifications should be
 * made in the mediawiki/LocalSettings.php file. See the README for
 * instructions.
 */

/**
 * Application metadata and credits. Should not be changed.
 */

$wgBugzillaExtVersion = '1.0.0';
$wgBugzillaQueryDefaults = [ 'ssl_verify_peer' => false ];

$wgExtensionCredits['other'][] = array(
    'name'        => 'Bugzilla',
    'author'      => 'Christian Legnitto',
    'url'         => 'https://github.com/LegNeato/mediawiki-bugzilla',
    'descriptionmsg' => 'bugzilla-desc',
    'license-name' => 'MPL2',
    'version'     => $wgBugzillaExtVersion,
);

$wgResourceModules['ext.Bugzilla'] = array(
    'scripts' => array( 'web/js/jquery.dataTables.js' ),
    'styles' => array( 'web/css/demo_page.css', 'web/css/demo_table.css', 'web/css/bugzilla.css' ),
    'messages' => array( 'bugzilla-hello-world', 'bugzilla-goodbye-world' ),
    'dependencies' => array( 'jquery.ui.core' ),
    'position' => 'top', // jquery.dataTables.js errors otherwise :(
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'Bugzilla'
);

/**
 * Classes to be autoloaded by mediawiki. Should you add any cache options, you
 * should include them in this list.
 */

$cwd = dirname(__FILE__); // We don't need to do this more than once!

$wgExtensionMessagesFiles['Bugzilla'] =  "$cwd/Bugzilla.i18n.php";

$wgAutoloadClasses['Bugzilla']           = $cwd . '/Bugzilla.class.php';
$wgAutoloadClasses['BugzillaQuery']      = $cwd . '/BugzillaQuery.class.php';
$wgAutoloadClasses['BugzillaOutput']     = $cwd . '/BugzillaOutput.class.php';

/**
 * These hooks are used by mediawiki to properly display the plugin information
 * and properly interpret the tags used.
 */

$wgHooks['BeforePageDisplay'][]          = 'BugzillaIncludeHTML';
$wgHooks['ParserFirstCallInit'][]        = 'BugzillaParserInit';

// Add content to page HTML
function BugzillaIncludeHTML( &$out, &$sk ) {

    global $wgScriptPath;
    global $wgVersion;
    global $wgBugzillaJqueryTable;
    global $wgBugzillaTable;

    if( $wgBugzillaJqueryTable ) {
        if( version_compare( $wgVersion, '1.17', '<') ) {
            // Use local jquery
            $out->addScriptFile("$wgScriptPath/extensions/Bugzilla/web/jquery/1.6.2/jquery.min.js");

            // Use local jquery ui
            $out->addScriptFile("$wgScriptPath/extensions/Bugzilla/web/jqueryui/1.8.14/jquery-ui.min.js");

            // Add a local jquery css file
            $out->addStyle("$wgScriptPath/extensions/Bugzilla/web/jqueryui/1.8.14/themes/base/jquery-ui.css");

            // Add a local jquery UI theme css file
            $out->addStyle("$wgScriptPath/extensions/Bugzilla/web/jqueryui/1.8.14/themes/smoothness/jquery-ui.css");

            // Add a local script file for the datatable
            $out->addScriptFile("$wgScriptPath/extensions/Bugzilla/web/js/jquery.dataTables.js");

            // Add local datatable styles
            $out->addStyle("$wgScriptPath/extensions/Bugzilla/web/css/demo_page.css");
            $out->addStyle("$wgScriptPath/extensions/Bugzilla/web/css/demo_table.css");

            // Add local bugzilla extension styles
            $out->addStyle("$wgScriptPath/extensions/Bugzilla/web/css/bugzilla.css");

        }

        // Add the script to do table magic
        $out->addInlineScript('$(document).ready(function() {
            $("table.bugzilla").dataTable({
            "bJQueryUI": true,
            "aLengthMenu": ' . $wgBugzillaTable['lengthMenu'] . ',
            "iDisplayLength" : ' . $wgBugzillaTable['pageSize'] . ',
            /* Disable initial sort */
            "aaSorting": [],
            })});'
        );
    }

    // Let the user optionally override bugzilla extension styles
    if( file_exists("$wgScriptPath/extensions/Bugzilla/web/css/custom.css") ) {
        $out->addStyle("$wgScriptPath/extensions/Bugzilla/web/css/custom.css");
    }

    $out->addModules('ext.Bugzilla');

    // Let the other hooks keep processing
    return TRUE;
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
function BugzillaRender($input, array $args, Parser $parser, $frame=null ) {
    global $wgBugzillaRESTURL;

    // We don't want the page to be cached
    // TODO: Not sure if we need this
    $parser->disableCache();

    // TODO: Figure out to have the parser not do anything to our output
    // mediawiki docs are wrong :-(
    // error_log(print_r($parser->mStripState, true));
    // $parser->mStripState->addItem( 'nowiki', 'NOWIKI', true);
    // 'noparse' => true, 'isHTML' => true, 'markerType' => 'nowiki' );

    $input = $parser->recursiveTagParse($input, $frame);

    // Create a new bugzilla object
    $bz = Bugzilla::create($args, $input, $parser->getTitle());

    // Show the desired output (or an error if there was one)
    $bz->fetch();
    return $bz->render();
}

/**
 * This configuration is the default configuration for mediawiki-bugzilla.
 * Please feel free to customize it for your environment. Be sure to make
 * changes in the mediawiki/LocalSettings.php file, to ensure upgrade
 * compatibility.
 */

// Remote API
$wgBugzillaRESTURL     = 'https://bugzilla.mozilla.org/bzapi'; // The URL for your Bugzilla API installation
$wgBugzillaURL         = 'https://bugzilla.mozilla.org'; // The URL for your Bugzilla installation
$wgBugzillaTagName     = 'bugzilla'; // The tag name for your Bugzilla installation (default: 'bugzilla')
$wgBugzillaMethod      = 'REST'; // XML-RPC and JSON-RPC may be supported later

// Cache
// NOTE: $wgBugzillaUseCache has been removed. Use $wgBugzillaCacheType below only:
// NOTE: $wgBugzillaUseCache has been removed. $wgBugzillaCacheType has been removed as well.
// NOTE: This extension now relies on what cache is available through MediaWiki directly;
// NOTE: see $wgMainCacheType in LocalSettings.php

$wgBugzillaCacheTimeOut = 5; // Minutes to cache results (default: 5)

$wgBugzillaJqueryTable = true; // Use a jQuery table for display (default: true)

// Charts
$wgBugzillaChartStorage = __DIR__ . '/charts'; // Location to store generated bug charts
$wgBugzillaFontStorage = __DIR__ . '/pchart/fonts'; // Path to font directory for font data
$wgBugzillaChartUrl = $wgScriptPath . '/extensions/Bugzilla/charts'; // The URL to use to display charts

// The default fields to display
$wgBugzillaDefaultFields = array(
    'id',
    'summary',
    'priority',
    'status',
);

$wgBugzillaTable = array(
  'pageSize' => 10, //default pagination count
  'lengthMenu' => '[[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]', //default length set
);
