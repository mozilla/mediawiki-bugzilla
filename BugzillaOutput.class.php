<?php

require_once('smarty/Smarty.class.php');

abstract class BugzillaOutput {

    public $response;
    public $cache;
    
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
    
    protected function _getCache()
    {
        global $wgCacheObject;
        if(!$this->cache) {
            $this->cache = new $wgCacheObject;
        }
        
        return $this->cache;
    }
    
    abstract public function _setup_template_data();
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
        $pData->addPoints($this->query->data->data, 'Counts');
        $pData->setAxisName(0, 'Bugs');
        $pData->addPoints($this->query->data->x_labels, "Bugs");
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
