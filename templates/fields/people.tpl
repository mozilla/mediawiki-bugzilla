<?php

    $out = '';
    $rn  = '';
    $n   = '';

    if( is_array($data) ) {

        if( isset($data['real_name']) && !empty($data['real_name']) ) {
            $rn  = '<span class="bugzilla-field-' . $field .'-real_name">';
            $rn .= htmlspecialchars($bug[$field]['real_name']);
            $rn .= '</span>';
        }

        if( isset($data['name']) && !empty($data['name']) ) {
            $n  = '<span class="bugzilla-field-' . $field .'-name">';
            $n .= htmlspecialchars($bug[$field]['name']);
            $n .= '</span>';
        }

        $out = ( empty($n) ) ? $rn : "$rn ($n)";

        // Special case for "nobody"
        if( isset($data['name']) && (empty($data['name']) || $data['name'] == 'nobody') ) {
            $out = '';
        }

    }else {
        $out = htmlspecialchars($data);
    }

    echo $out;
?>
