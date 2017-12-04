<?php
//
// Description
// -----------
// This function returns the list of objects and object_ids that should be checked in link checker module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_artistprofiles_hooks_linkcheckerList($ciniki, $tnid, $args) {

    $objects = array();

    //
    // Get the list of items that should be in the index
    //
    $strsql = "SELECT CONCAT('ciniki.artistprofiles.link.', id) AS oid, "
        . "'ciniki.artistprofiles.link' AS object, "
        . "id AS object_id, "
        . "url "
        . "FROM ciniki_artistprofiles_links "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.artistprofiles', array(
        array('container'=>'objects', 'fname'=>'oid', 'fields'=>array('object', 'object_id', 'url')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['objects']) ) {
        $objects = $rc['objects'];
    }

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
