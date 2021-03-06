<?php
//
// Description
// ===========
// This method will return all the information about an image.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the image is attached to.
// artist_image_id:          The ID of the image to get the details for.
//
// Returns
// -------
//
function ciniki_artistprofiles_imageGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'artist_image_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Image'),
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
    $rc = ciniki_artistprofiles_checkAccess($ciniki, $args['tnid'], 'ciniki.artistprofiles.imageGet');
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
    // Return default for new Image
    //
    if( $args['artist_image_id'] == 0 ) {
        $image = array('id'=>0,
            'artist_id'=>'',
            'name'=>'',
            'permalink'=>'',
            'flags'=>1,
            'image_id'=>'0',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Image
    //
    else {
        $strsql = "SELECT ciniki_artistprofiles_images.id, "
            . "ciniki_artistprofiles_images.artist_id, "
            . "ciniki_artistprofiles_images.name, "
            . "ciniki_artistprofiles_images.permalink, "
            . "ciniki_artistprofiles_images.flags, "
            . "ciniki_artistprofiles_images.image_id, "
            . "ciniki_artistprofiles_images.description "
            . "FROM ciniki_artistprofiles_images "
            . "WHERE ciniki_artistprofiles_images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_artistprofiles_images.id = '" . ciniki_core_dbQuote($ciniki, $args['artist_image_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artistprofiles', 'image');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artistprofiles.25', 'msg'=>'Image not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['image']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artistprofiles.26', 'msg'=>'Unable to find Image'));
        }
        $image = $rc['image'];
    }

    return array('stat'=>'ok', 'image'=>$image);
}
?>
