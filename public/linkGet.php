<?php
//
// Description
// ===========
// This method will return all the information about an link.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the link is attached to.
// link_id:          The ID of the link to get the details for.
//
// Returns
// -------
//
function ciniki_artistprofiles_linkGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'link_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Link'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'checkAccess');
    $rc = ciniki_artistprofiles_checkAccess($ciniki, $args['tnid'], 'ciniki.artistprofiles.linkGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Return default for new Link
    //
    if( $args['link_id'] == 0 ) {
        $link = array('id'=>0,
            'artist_id'=>'',
            'name'=>'',
            'link_type'=>'1000',
            'url'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Link
    //
    else {
        $strsql = "SELECT ciniki_artistprofiles_links.id, "
            . "ciniki_artistprofiles_links.artist_id, "
            . "ciniki_artistprofiles_links.name, "
            . "ciniki_artistprofiles_links.link_type, "
            . "ciniki_artistprofiles_links.url, "
            . "ciniki_artistprofiles_links.description "
            . "FROM ciniki_artistprofiles_links "
            . "WHERE ciniki_artistprofiles_links.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_artistprofiles_links.id = '" . ciniki_core_dbQuote($ciniki, $args['link_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artistprofiles', 'link');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artistprofiles.30', 'msg'=>'Link not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['link']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artistprofiles.31', 'msg'=>'Unable to find Link'));
        }
        $link = $rc['link'];
    }

    return array('stat'=>'ok', 'link'=>$link);
}
?>
