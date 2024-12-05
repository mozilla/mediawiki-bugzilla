<?php

abstract class BugzillaOutput {

    public $response;

    public function __construct($config, $options, $title='') {
        $this->title    = $title;
        $this->config   = $config;
        $this->error    = false;
        $this->response = new stdClass();

        $this->query = BugzillaQuery::create($config['type'], $options, $title);
    }

    protected function _render_error($error) {
        $this->template = dirname(__FILE__) . '/templates/error.tpl';
        ob_start(); // Start output buffering.
        require($this->template);
        return ob_get_clean();
    }

    public function fetch() {
        $this->query->fetch();
    }

    public function render() {
        if( $this->query->error ) {
            return $this->_render_error($this->query->error);
        }

        // Get our template path
        $this->template = dirname(__FILE__) . '/templates/' .
                          $this->config['type'] . '/' .
                          $this->config['display'] . '.tpl';

        // Make sure a template is there
        if( !file_exists($this->template) ) {
            $this->error = 'Invalid type ' .
                           '(' . htmlspecialchars($this->config['type']) . ')' .
                           ' and display ' .
                           '(' . htmlspecialchars($this->config['display']) . ')' .
                           ' combination';
        }

        if( $this->error ) {
            return $this->_render_error($this->error);
        }

        $this->setup_template_data();

        $response = $this->response;
        ob_start(); // Start output buffering.
        require($this->template);
        $results = ob_get_clean();
        return $results;

    }

    abstract protected function setup_template_data();
}

class BugzillaNumber extends BugzillaOutput {
    function setup_template_data() {
    }

    function _render_error($error) {
        return '<span style="color: red;">Bugzilla: '.htmlspecialchars($error).'</span>';
    }

    function render() {
        return '<span>'.count($this->query->data['bugs']).'</span>';
    }
}

class BugzillaBugListing extends BugzillaOutput {

    protected function setup_template_data() {

        global $wgBugzillaDefaultFields;

        $this->response->bugs   = array();
        $this->response->fields = array();
        $this->response->full_query_url = $this->query->full_query_url();

        // Set the bug data for the templates
        if(isset($this->query->data['bugs']) && count($this->query->data['bugs']) > 0) {
            $this->response->bugs = $this->query->data['bugs'];
        }

        $this->response->fields = $this->query->options['include_fields'];
    }

}

class BugzillaList extends BugzillaBugListing {

}

class BugzillaTable extends BugzillaBugListing {

}