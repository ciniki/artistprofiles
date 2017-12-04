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
function ciniki_artistprofiles_artistList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'category_permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'checkAccess');
    $rc = ciniki_artistprofiles_checkAccess($ciniki, $args['tnid'], 'ciniki.artistprofiles.artistList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of artists
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.artistprofiles', 0x0100) 
        && isset($args['category_permalink']) && $args['category_permalink'] != '' 
        ) {
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.artistprofiles', 0x0200) && $args['category_permalink'] == 'featured' ) {
            $strsql = "SELECT ciniki_artistprofiles.id, "
                . "ciniki_artistprofiles.name, "
                . "ciniki_artistprofiles.sort_name, "
                . "ciniki_artistprofiles.permalink, "
                . "ciniki_artistprofiles.status, "
                . "ciniki_artistprofiles.flags "
                . "FROM ciniki_artistprofiles "
                . "WHERE ciniki_artistprofiles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND (flags&0x01) = 0x01 "
                . "ORDER BY sort_name "
                . "";
        } else {
            $strsql = "SELECT ciniki_artistprofiles.id, "
                . "ciniki_artistprofiles.name, "
                . "ciniki_artistprofiles.sort_name, "
                . "ciniki_artistprofiles.permalink, "
                . "ciniki_artistprofiles.status, "
                . "ciniki_artistprofiles.flags "
                . "FROM ciniki_artistprofiles_tags, ciniki_artistprofiles "
                . "WHERE ciniki_artistprofiles_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_artistprofiles_tags.tag_type = 10 "
                . "AND ciniki_artistprofiles_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category_permalink']) . "' "
                . "AND ciniki_artistprofiles_tags.artist_id = ciniki_artistprofiles.id "
                . "AND ciniki_artistprofiles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY sort_name "
                . "";
        }
    } else {
        $strsql = "SELECT ciniki_artistprofiles.id, "
            . "ciniki_artistprofiles.name, "
            . "ciniki_artistprofiles.sort_name, "
            . "ciniki_artistprofiles.permalink, "
            . "ciniki_artistprofiles.status, "
            . "ciniki_artistprofiles.flags "
            . "FROM ciniki_artistprofiles "
            . "WHERE ciniki_artistprofiles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY sort_name "
            . "";
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

    $rsp = array('stat'=>'ok', 'artists'=>$artists);

    //
    // Return list of categories
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.artistprofiles', 0x0100) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'web', 'subMenuItems');
        $rc = ciniki_artistprofiles_web_subMenuItems($ciniki, array(), $args['tnid'], array());
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['categories'] = $rc['submenu'];
    }

    return $rsp;
}
?>
