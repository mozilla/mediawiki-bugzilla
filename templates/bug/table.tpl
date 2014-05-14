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
            $verified = 0;

            foreach( $response->bugs as $bug ) {

                if($bug['status'] == 'RESOLVED') {
                    $resolved++;
                }
                if($bug['status'] == 'VERIFIED') {
                    $verified++;
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

<strong>
<?php echo $all ?> Total;
<?php echo $all-$resolved-$verified ?> Open (<?php if ($all != 0) echo 100*(round(($all-$resolved-$verified)/$all, 4)); else echo 0 ?>%);
<?php echo $resolved ?> Resolved (<?php if ($all != 0) echo 100*(round(($resolved)/$all, 4)); else echo 0 ?>%);
<?php echo $verified ?> Verified (<?php if ($all != 0) echo 100*(round(($verified)/$all, 4)); else echo 0 ?>%);
</strong>
