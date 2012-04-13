<?php

abstract class BugzillaOutput {

    public $response;
    public $cache;
    
    public function __construct($config, $options, $title='') {
        $this->title    = $title;
        $this->config   = $config;
        $this->error    = FALSE;
        $this->response = new stdClass();

        // Make our query and possibly fetch the data
        $this->query = BugzillaQuery::create($config['type'], $options, $title);

        // Bubble up any query errors
        if( $this->query->error ) {
            $this->error = $this->query->error;
        }
    }

    protected function _render_error($error) {
        $this->template = dirname(__FILE__) . '/templates/error.tpl';
        ob_start(); // Start output buffering.
        require($this->template);
        return ob_get_clean();
    }

    public function render() {

        // Get our template path
        $this->template = dirname(__FILE__) . '/templates/' . 
                          $this->config['type'] . '/' . 
                          $this->config['display'] . '.tpl';

        // Make sure a template is there
        if( !file_exists($this->template) ) {
            $this->error = 'Invalid type and display combination';
        }

        // If there are any errors (either from the template path above or 
        // elsewhere) output them
        if( $this->error ) {
            return $this->_render_error($this->error);
        }

        $this->_setup_template_data();

        $response = $this->response;
        ob_start(); // Start output buffering.
        require($this->template);
        $results = ob_get_clean();
        return $results;

    }
    
    protected function _getCache()
    {
        global $wgCacheObject;
        if(!$this->cache) {
            $this->cache = new $wgCacheObject;
        }
        
        return $this->cache;
    }

    abstract protected function _setup_template_data();

}

class BugzillaBugListing extends BugzillaOutput {
    
    protected function _setup_template_data() {

        global $wgBugzillaDefaultFields;

        $this->response->bugs   = array();
        $this->response->fields = array();

        // Set the bug data for the templates
        if(count($this->query->data['bugs']) > 0) {
            $this->response->bugs = $this->query->data['bugs'];
        }

        // Set the field data for the templates
        if( isset($this->query->options['include_fields']) &&
            !empty($this->query->options['include_fields']) ) {
            // User specified some fields
            $tmp = @explode(',', $this->query->options['include_fields']);
            foreach( $tmp as $tmp_field ) {
                $field = trim($tmp_field);
                // Catch if the user specified the same field multiple times
                if( !empty($field) && 
                    !in_array($field, $this->response->fields) ) {
                    array_push($this->response->fields, $field);
                }
            }
        }else {
            // If the user didn't specify any fields in the query config use
            // default fields
            $this->response->fields = $wgBugzillaDefaultFields;
        }
    }

}

class BugzillaList extends BugzillaBugListing {

}

class BugzillaTable extends BugzillaBugListing {

}

abstract class BugzillaGraph extends BugzillaOutput {

}

include 'pchart/class/pDraw.class.php';
include 'pchart/class/pImage.class.php';
include 'pchart/class/pData.class.php';

class BugzillaBarGraph extends BugzillaGraph {

    public function generate_chart($chart_name)
    {
        global $wgBugzillaChartStorage, $wgBugzillaFontStorage;
        $pData = new pData();
        $pData->addPoints($this->query->data['data'], 'Counts');
        $pData->setAxisName(0, 'Bugs');
        $pData->addPoints($this->query->data['x_labels'], "Bugs");
        $pData->setSerieDescription("Bugs", "Bugs");
        $pData->setAbscissa("Bugs");

        $pImage = new pImage(600,300, $pData);
        $pImage->setFontProperties(array('FontName' => $wgBugzillaFontStorage . '/verdana.ttf', 'FontSize' => 6));
        $pImage->setGraphArea(75, 30, 580, 280);
        $pImage->drawScale(array("CycleBackground"=>TRUE,"DrawSubTicks"=>FALSE,"GridR"=>0,"GridG"=>0,"GridB"=>0,"GridAlpha"=>10, "Pos"=>SCALE_POS_TOPBOTTOM)); 

        $pImage->drawBarChart();
        $pImage->render($wgBugzillaChartStorage . '/' . $chart_name . '.png');
        $cache = $this->_getCache();
        $cache->set($chart_name, $chart_name . '.png');
        return $chart_name;
    }

    public function _setup_template_data() {
        global $wgBugzillaChartUrl;
        $key = md5($this->query->id . '_bar_chart');
        $cache = $this->_getCache();
        if($result = $cache->get($key)) {
            $image = $result['data'];
            $this->response->image = $wgBugzillaChartUrl . '/' . $image;
        } else {
            $this->response->image = $wgBugzillaChartUrl . '/' . $this->generate_chart($key) . '.png';
        } 
    }
}

?>
