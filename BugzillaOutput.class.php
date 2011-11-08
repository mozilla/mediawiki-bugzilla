<?php

require_once('smarty/Smarty.class.php');

abstract class BugzillaOutput {

    public $response;

    public function __construct($config, $options, $title='') {
        $this->title = $title;
        $this->config = $config;
        $this->response = new stdClass();

        // Make our query and possibly fetch the data
        $this->query = BugzillaQuery::create($config['type'], $options, $title);


        //error_log($this->query);
        //error_log($this->query->id());
        //error_log(print_r($this->query->data, true));

    }

    protected function _render_error() {
        $what = (!empty($this->error)) ? $this->error : 'Unknown Error';
        return "<div class='bugzilla error'>Bugzilla Error: $what</div>";
    }

    public function render() {
        // Get our template path
        $this->template = dirname(__FILE__) . '/templates/' . 
                          $this->config['type'] . '/' . 
                          $this->config['display'] . '.tpl';

        //error_log($this->template);

        // Make sure a template is there
        if( !file_exists($this->template) ) {
            $this->error = 'Invalid type and display combination';
        }

        $this->_setup_template_data();

        $response = $this->response;
        ob_start(); // Start output buffering.
        require($this->template);
        $results = ob_get_clean();
        return $results;

    }
}

class BugzillaTable extends BugzillaOutput {

    public function _setup_template_data() {
        if(count($this->query->data->bugs) > 0) {
            $this->response->bugs = $this->query->data->bugs;
        } else {
            $this->response->bugs = array();
        }
    }
}

class BugzillaGraph extends BugzillaOutput {

}

class BugzillaBarGraph extends BugzillaGraph {

    public function _setup_template_data() {
        $this->response->type = "bhs";
        $this->response->size = '200x300';
        $this->response->x_labels = implode('|', $this->query->data->x_labels);
        $this->response->data = implode(',', $this->query->data->data);
    }
}

?>
