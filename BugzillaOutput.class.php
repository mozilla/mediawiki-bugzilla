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
        if( !empty($this->query->data['bugs']) ) {
            $this->response->bugs = $this->query->data['bugs'];
        }

        $this->response->fields = $this->query->options['include_fields'];
    }

}

class BugzillaList extends BugzillaBugListing {

}

class BugzillaTable extends BugzillaBugListing {

}


/* Graphing */

abstract class BugzillaGraph extends BugzillaOutput {

    public function __construct($config, $options, $title = '') {
        parent::__construct($config, $options, $title);

        if (!extension_loaded('gd') && !extension_loaded('gd2')) {
            $this->error = 'GD extension must be loaded.';
        }
    }

    protected function _get_size() {

        if ( isset( $this->config['size'] ) ) {

            switch($this->config['size']) {
                // whitelist
            case 'small':
            case 'medium':
            case 'large':
                return $this->config['size'];
            }
        }
        return 'large';
    }

    public function setup_template_data() {
        include_once 'pchart/class/pDraw.class.php';
        include_once 'pchart/class/pImage.class.php';
        include_once 'pchart/class/pData.class.php';

        global $wgBugzillaChartUrl;

        $key = md5($this->query->id . $this->_get_size() . get_class($this));
        $cache = $this->_getCache();
        if($result = $cache->get($key)) {
            $image = $result;
            $this->response->image = $wgBugzillaChartUrl . '/' . $image;
        } elseif ( !empty( $this->query->data['data'] ) ) {
            $this->response->image = $wgBugzillaChartUrl . '/' . $this->generate_chart($key) . '.png';
        } else {
            $this->response->image = "";
        }

        $this->response->image = $wgBugzillaChartUrl.'/'.$fileName.'.png';
    }

}

class BugzillaPieGraph extends BugzillaGraph {

    public function generate_chart($chart_name)
    {
        if ( empty( $this->query->data['data'] ) ) {
            return "";
        }
        include_once "pchart/class/pPie.class.php";

        global $wgBugzillaChartStorage;
        global $wgBugzillaFontStorage;

        // TODO: Make all this size stuff trivial for other
        // graph types to plug into
        switch($this->_get_size()) {
            case 'small':
                $imgX = 200;
                $imgY = 65;
                $radius = 30;
                $font = 6;
                break;

            case 'medium':
                $imgX = 400;
                $imgY = 125;
                $radius = 60;
                $font = 7;
                break;

            case 'large':
            default:
                $imgX = 500;
                $imgY = 245;
                $radius = 120;
                $font = 9;
        }

        $padding = 5;

        $startX = ( isset($startX) ) ? $startX : $radius;
        $startY = ( isset($startY) ) ? $startY : $radius;

        $pData = new pData();
        $pData->addPoints($this->query->data['data'], 'Counts');
        $pData->setAxisName(0, 'Bugs');
        $pData->addPoints($this->query->data['x_labels'], "Bugs");
        $pData->setSerieDescription("Bugs", "Bugs");
        $pData->setAbscissa("Bugs");

        $pImage = new pImage($imgX, $imgY, $pData);
        $pImage->setFontProperties(array('FontName' => $wgBugzillaFontStorage . '/verdana.ttf', 'FontSize' => $font));
        $pPieChart = new pPie($pImage, $pData);

        $pPieChart->draw2DPie($startX,
                              $startY,
                              array(
                                  "Radius" => $radius,
                                  "ValuePosition" => PIE_VALUE_INSIDE,
                                  "WriteValues"=>PIE_VALUE_NATURAL,
                                  "DrawLabels"=>FALSE,
                                  "LabelStacked"=>TRUE,
                                  "ValueR" => 0,
                                  "ValueG" => 0,
                                  "ValueB" => 0,
                                  "Border"=>TRUE));

        // Legend
        $pImage->setShadow(FALSE);
        $pPieChart->drawPieLegend(2*$radius + 2*$padding, $padding, array("Alpha"=>20));

        $pImage->render($wgBugzillaChartStorage . '/' . $chart_name . '.png');

        return $chart_name;
    }
}

class BugzillaBarGraph extends BugzillaGraph {

    public function generate_chart($chart_name)
    {
        global $wgBugzillaChartStorage, $wgBugzillaFontStorage;
        if ( empty( $this->query->data['data'] ) ) {
            return "";
        }
        $pData = new pData();
        $pData->addPoints($this->query->data['data'], 'Counts');
        $pData->setAxisName(0, 'Bugs');
        $pData->addPoints($this->query->data['x_labels'], "Bugs");
        $pData->setSerieDescription("Bugs", "Bugs");
        $pData->setAbscissa("Bugs");

        $pImage = new pImage(600,300, $pData);
        $pImage->setFontProperties(array('FontName' => $wgBugzillaFontStorage . '/verdana.ttf', 'FontSize' => 6));
        $pImage->setGraphArea(75, 30, 580, 280);
        $pImage->drawScale(array("CycleBackground"=>TRUE,'Factors'=>array(1),"DrawSubTicks"=>FALSE,"GridR"=>0,"GridG"=>0,"GridB"=>0,"GridAlpha"=>10, "Pos"=>SCALE_POS_TOPBOTTOM)); 

        $pImage->drawBarChart();
        $pImage->render($wgBugzillaChartStorage . '/' . $chart_name . '.png');

        return $chart_name;
    }

}
