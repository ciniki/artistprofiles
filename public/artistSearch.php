<?php
//
// Description
// -----------
// This method will return the list of Artists for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Artist for.
//
// Returns
// -------
//
function ciniki_artistprofiles_artistSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'default'=>'15', 'name'=>'Limit'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'checkAccess');
    $rc = ciniki_artistprofiles_checkAccess($ciniki, $args['tnid'], 'ciniki.artistprofiles.artistSearch');
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
        . "WHERE ciniki_artistprofiles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (name like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name like '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "ORDER BY sort_name "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";   // is_numeric verified
    } else {
        $strsql .= "LIMIT 25 ";
    }
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
