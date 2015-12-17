<?php
//
// Description
// -----------
// This method will return the list of Artists for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Artist for.
//
// Returns
// -------
//
function ciniki_artistprofiles_artistList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'checkAccess');
    $rc = ciniki_artistprofiles_checkAccess($ciniki, $args['business_id'], 'ciniki.artistprofiles.artistList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of artists
    //
    $strsql = "SELECT ciniki_artistprofiles.id, "
        . "ciniki_artistprofiles.name, "
        . "ciniki_artistprofiles.sort_name, "
        . "ciniki_artistprofiles.permalink, "
        . "ciniki_artistprofiles.status, "
        . "ciniki_artistprofiles.flags "
        . "FROM ciniki_artistprofiles "
        . "WHERE ciniki_artistprofiles.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY sort_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.artistprofiles', array(
        array('container'=>'artists', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'sort_name', 'permalink', 'status', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['artists']) ) {
        $artists = $rc['artists'];
    } else {
        $artists = array();
    }

    return array('stat'=>'ok', 'artists'=>$artists);
}
?>
