<ul>
    <?php
        $base = dirname(__FILE__) . '/../../templates/fields/';

        foreach( $response->bugs as $bug ) {
            echo "<li class='bugzilla-status-${bug['status']}'>";
            $count = 0;
            foreach( $response->fields as $field ) {
                if( $count ) {
                    echo " - ";
                }
                echo "<span class='bugzilla-data-$field'>";

                // Get our template path
                $subtemplate = $base . 
                    escapeshellcmd(str_replace('..',
                        'DOTS',
                        $field
                    )
                ) . '.tpl';

                // Make sure a template is there
                if( !file_exists($subtemplate) ) {
                    $subtemplate = $base . '_default.tpl';
                }

                // Print out the data
                $data = null;
                if ( isset( $bug[$field] ) ) {
                    $data = $bug[$field];
                }
                require($subtemplate);

                echo "</span>";
                $count++;
            }
            echo "</li>\n";
        }
    ?>
</ul>
