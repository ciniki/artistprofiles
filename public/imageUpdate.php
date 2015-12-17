<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_artistprofiles_imageUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'artist_image_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Image'),
        'artist_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Artist'),
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'),
        'flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Options'),
        'image_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Image'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'checkAccess');
    $rc = ciniki_artistprofiles_checkAccess($ciniki, $args['business_id'], 'ciniki.artistprofiles.imageUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

	//
	// Get the existing image details
	//
	$strsql = "SELECT artist_id, uuid, image_id "
        . "FROM ciniki_artistprofiles_images "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['artist_image_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artistprofiles', 'item');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['item']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2893', 'msg'=>'Artist image not found'));
	}
	$item = $rc['item'];

	if( isset($args['name']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        if( isset($args['name']) && $args['name'] != '' ) {
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        } else {
            $args['permalink'] = ciniki_core_makePermalink($ciniki, $item['uuid']);
        }
		//
		// Make sure the permalink is unique
		//
		$strsql = "SELECT id, name, permalink "
            . "FROM ciniki_artistprofiles_images "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND artist_id = '" . ciniki_core_dbQuote($ciniki, $item['artist_id']) . "' "
			. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
			. "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['artist_image_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artistprofiles', 'image');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( $rc['num_rows'] > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2894', 'msg'=>'You already have an image with this name, please choose another name'));
		}
	}

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.artistprofiles');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Image in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.artistprofiles.image', $args['artist_image_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.artistprofiles');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'artistprofiles');

    return array('stat'=>'ok');
}
?>
