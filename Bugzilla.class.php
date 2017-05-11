<?php
# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.

$dir = dirname(__FILE__);
require_once ($dir . '/BugzillaOutput.class.php');

// Factory
class Bugzilla {

    public static function create($config=array(), $opts=array(), $title='') {
        // Default configuration
        // FIXME: This should be in the main configuration
        $theconfig = array(
            'type'    => 'bug',
            'display' => 'table',
            'stats'   => 'show',
        );

        // Overlay user's desired configuration
        foreach( $config as $key => $value ) {
            $theconfig[$key] = $value;
        }

        $classes = [
            'list'   => 'List',
            'number' => 'Number',
            'bar'    => 'BarGraph',
            'vbar'   => 'VerticalBarGraph',
            'pie'    => 'PieGraph',
            'inline' => 'Inline',
            'table'  => 'Table',
        ];
        if (!array_key_exists($theconfig['display'], $classes)) {
            $theconfig['display'] = 'table';
        }

        $class = 'Bugzilla'.$classes[$theconfig['display']];

        return new $class($theconfig, $opts, $title);
    }
}
