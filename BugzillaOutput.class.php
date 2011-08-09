<?php

require_once('smarty/Smarty.class.php');

abstract class BugzillaOutput {

    public function __construct($config, $options, $title='') {
        $this->title = $title;
        $this->config = $config;

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
        global $wgBugzillaURL;
        global $wgBugzillaSmartyTemplateDir;
        global $wgBugzillaSmartyCompileDir;
        global $wgBugzillaSmartyConfigDir;
        global $wgBugzillaSmartyCacheDir;

        // Get our template path
        $this->template = dirname(__FILE__) . '/templates/' . 
                          $this->config['type'] . '/' . 
                          $this->config['display'] . '.tpl';

        //error_log($this->template);

        // Make sure a template is there
        if( !file_exists($this->template) ) {
            $this->error = 'Invalid type and display combination';
        }

        $this->smarty = new Smarty();
        $this->smarty->assign('bz_url', $wgBugzillaURL);
        $this->smarty->template_dir = $wgBugzillaSmartyTemplateDir;
        $this->smarty->compile_dir  = $wgBugzillaSmartyCompileDir;
        $this->smarty->config_dir   = $wgBugzillaSmartyConfigDir;
        $this->smarty->cache_dir    = $wgBugzillaSmartyCacheDir;

        $this->_setup_template_data();

        // Bail if we get any errors
        if( isset($this->error) && !empty($this->error))  {
            return $this->_render_error();
        }

        return $this->smarty->fetch($this->template);

    }
}

class BugzillaTable extends BugzillaOutput {

    public function _setup_template_data() {
        $this->smarty->assign('bugs', $this->query->data->bugs);
    }
}

class BugzillaGraph extends BugzillaOutput {

}

class BugzillaBarGraph extends BugzillaGraph {

    public function _setup_template_data() {
        $this->smarty->assign('type', 'bhs');
        #$smarty->assign('type', 'p');
        $this->smarty->assign('size', '200x300');
        $this->smarty->assign('x_labels', implode('|', $this->query->data->x_labels));
        $this->smarty->assign('data', implode(',', $this->query->data->data));
    }
}

?>
