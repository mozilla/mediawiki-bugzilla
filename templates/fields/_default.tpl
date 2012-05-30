<?php
    echo "<span class='bugzilla-field-$field'>";
    if( is_array($bug[$field]) ) {
        echo htmlspecialchars(implode(', ', $bug[$field]));
    }else{
        echo htmlspecialchars($bug[$field]);
    }
    echo "</span>";
?>
