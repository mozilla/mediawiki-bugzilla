<?php
    global $wgBugzillaJqueryTable;
    $extra_class = ($wgBugzillaJqueryTable) ? 'jquery ui-helper-reset' : '';
?>
<table class="bugzilla <?php echo $extra_class ?>">
    <thead>
        <tr>
        <?php
            foreach( $response->fields as $field ) {
                echo "<th>";
                switch( $field ) {
                    case 'id':
                        echo 'ID';
                        break;
                    default:
                        echo htmlspecialchars(
                            ucfirst(
                                str_replace('_', ' ',
                                    preg_replace('/^cf_/', '', $field)
                                )
                            )
                        );
                }
                echo "</th>\n";
            }
        ?>
        </tr>
    </thead>
    <tbody>
        <?php
            $base = dirname(__FILE__) . '/../../templates/fields/';
            
            $all = count($response->bugs);
            $resolved = 0;
            
            foreach( $response->bugs as $bug ) {
                
                if($bug['status'] == 'RESOLVED') {
                    $resolved++;
                }
                
                echo "<tr class='bugzilla-status-${bug['status']}'>";
                foreach( $response->fields as $field ) {
                    echo "<td class='bugzilla-data-$field'>";

                    // Get our template path
                    $subtemplate = $base .
                        escapeshellcmd(
                            str_replace('..', 'DOTS', $field)
                        ) . '.tpl';

                    // Make sure a template is there
                    if( !file_exists($subtemplate) ) {
                        $subtemplate = $base . '_default.tpl';
                    }

                    // Print out the data
                    $data = $bug[$field];
                    require($subtemplate);

                    echo "</td>\n";
                }
                echo "</tr>\n";
            }
        ?>
    </tbody>
</table>

<strong><a href="?action=purge">Refresh Bugs</a>&nbsp;&nbsp;&nbsp;<?= $all-$resolved ?> Open; <?= $resolved ?> Resolved; <?= $all ?> Total (<?php echo 100*(round($resolved/$all, 4)) ?>% complete)</strong>
