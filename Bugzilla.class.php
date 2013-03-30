<?php

require_once(dirname(__FILE__) . '/BugzillaOutput.class.php');

// Factory
class Bugzilla {

    public static function create($config=array(), $opts=array(), $title='') {
        // Default configuration
        // FIXME: This should be in the main configuration
        $theconfig = array(
            'type'    => 'bug',
            'display' => 'table',
        );

        // Overlay user's desired configuration
        foreach( $config as $key => $value ) {
            $theconfig[$key] = $value;
        }

        // Generate the proper object
        switch( $theconfig['display'] ) {
            case 'list':
                $b = new BugzillaList($theconfig, $opts, $title);
                break;

            case 'bar':
                $b = new BugzillaBarGraph($theconfig, $opts, $title);
                break;

            case 'vbar':
                $b = new BugzillaVerticalBarGraph($theconfig, $opts, $title);
                 break;

            case 'pie':
                $b = new BugzillaPieGraph($theconfig, $opts, $title);
                break;

            case 'inline':
                $b = new BugzillaInline($theconfig, $opts, $title);
                break;

            case 'table':
            default:
                $b = new BugzillaTable($theconfig, $opts, $title);
        }

        return $b;

    }
}

