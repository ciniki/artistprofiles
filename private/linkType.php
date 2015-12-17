<?php
//
// Description
// -----------
// This function checks the url to decide which link type is should be.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_artistprofiles_linkType($ciniki, $business_id, $url) {
    $link_type = '1000';

    if( preg_match('/youtube.com/', $url) ) {
        $link_type = 2000;
    } elseif( preg_match('/vimeo.com/', $url) ) {
        $link_type = 2001;
    }

	return array('stat'=>'ok', 'link_type'=>$link_type);
}
?>
