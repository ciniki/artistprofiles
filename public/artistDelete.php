<?php
//
// Description
// -----------
// This method will delete an artist.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the artist is attached to.
// artist_id:            The ID of the artist to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_artistprofiles_artistDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'artist_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Artist'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'checkAccess');
    $rc = ciniki_artistprofiles_checkAccess($ciniki, $args['tnid'], 'ciniki.artistprofiles.artistDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the artist
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_artistprofiles "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['artist_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artistprofiles', 'artist');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['artist']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artistprofiles.17', 'msg'=>'Airlock does not exist.'));
    }
    $artist = $rc['artist'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.artistprofiles');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the artist
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.artistprofiles.artist',
        $args['artist_id'], $artist['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
        return $rc;
    }

    //
    // Remove any tags
    //
    if( ($ciniki['tenant']['modules']['ciniki.artistprofiles']['flags']&0x10) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsDelete');
        $rc = ciniki_core_tagsDelete($ciniki, 'ciniki.artistprofiles', 'tag', $args['tnid'],
            'ciniki_artistprofiles_tags', 'ciniki_artistprofiles_history', 'artist_id', $args['artist_id']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
            return $rc;
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.artistprofiles');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'artistprofiles');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.artistprofiles.artist', 'object_id'=>$args['artist_id']));

    return array('stat'=>'ok');
}
?>
