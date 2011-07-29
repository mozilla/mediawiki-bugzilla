<?php

require_once 'HTTP/Request2.php';
require_once('smarty/Smarty.class.php');
require_once(dirname(__FILE__) . '/Utils.php');

class Bugzilla {

    // Set variables and initialize the backend
    function __construct($url, $config=array(), $id=FALSE) {
        $this->url   = $url;
        $this->id    = $id;
        $this->error = FALSE;
        $this->data  = FALSE;

        $this->_configure($config);
    }

    private function _configure( $config ) {

        // Default configuration
        // TODO: This should be in the main configuration
        $this->config = array(
            'type'    => 'bug',
            'display' => 'table',
        );

        // Overlay user's desired configuration
        foreach( $config as $key => $value ) {
            $this->config[$key] = $value;
        }
    }

    // Connect and get the data
    public function fetch( $query_opts_raw = FALSE ) {

        // Don't do anything if we already had an error
        if( $this->error ) { return; }

        // TODO: To support loading from the DB in the future
        if( $this->id ) {
            $this->_fetch_by_id();
            return;
        }

        // Make sure query options are valid JSON
        $opts = json_decode($query_opts_raw);
        if( !$query_opts_raw || !$opts ) {
            $this->error = 'Query options must be valid json';
            return;
        }

        // Do the actual fetching
        $this->_fetch_by_options($opts);
    }

    private function _fetch_by_id() {
        // TODO: Stub
    }

    // Load data from the Bugzilla REST API
    private function _fetch_by_options($opts) {

        // Set up our HTTP request
        $request = new HTTP_Request2($this->url . "/" . $this->config['type'],
                                     HTTP_Request2::METHOD_GET,
                                     array('follow_redirects' => TRUE,
                                           // TODO: Not sure if I should do this
                                           'ssl_verify_peer' => FALSE));

        // The REST API requires these
        $request->setHeader('Accept', 'application/json');
        $request->setHeader('Content-Type', 'application/json');

        // Add in the requested query options
        $url = $request->getUrl();
        $url->setQueryVariables(get_object_vars($opts));

        // This is basically straight from the HTTP/Request2 docs
        try {
            $response = $request->send();
            if (200 == $response->getStatus()) {
                $this->data = json_decode($response->getBody());
            } else {
                $this->error = 'Server returned unexpected HTTP status: ' .
                               $response->getStatus() . ' ' .
                               $response->getReasonPhrase();
                return;
            }
        } catch (HTTP_Request2_Exception $e) {
            $this->error = $e->getMessage();
            return;
        }

        // Now that we have the data, process it
        $this->_process_data();

    }

    private function _process_data() {
        // TODO: Stub
    }

    public function render() {

        global $wgBugzillaSmartyTemplateDir;
        global $wgBugzillaSmartyCompileDir;
        global $wgBugzillaSmartyConfigDir;
        global $wgBugzillaSmartyCacheDir;

        // If we have an error, render it out instead
        if( $this->error ) {
            return $this->_render_error();
        }

        // No error, we're good to go
        $smarty = new Smarty();
        $smarty->template_dir = $wgBugzillaSmartyTemplateDir;
        $smarty->compile_dir  = $wgBugzillaSmartyCompileDir;
        $smarty->config_dir   = $wgBugzillaSmartyConfigDir;
        $smarty->cache_dir    = $wgBugzillaSmartyCacheDir;


        // TODO: This is basically a prototype, needs to be better
        if( $this->config['display'] == 'table' ) {
            $smarty->assign('bugs', $this->data->bugs);
            return $smarty->fetch('bug/table.tpl');

        elseif( $this->config['display'] == 'bar' ) {
            $smarty->assign('type', 'bhs');
            #$smarty->assign('type', 'p');
            $smarty->assign('size', '200x300');
            $smarty->assign('x_labels',  implode('|', $this->data->x_labels));
            $smarty->assign('data', implode(',', $this->data->data));
            return $smarty->fetch('count/bar.tpl');
        }

    }

    private function _render_error() {
        $what = (!empty($this->error)) ? $this->error : 'Unknown Error';
        return "<div class='bugzilla error'>Bugzilla Error: $what</div>";
    }

}


?>
