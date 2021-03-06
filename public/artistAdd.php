<?php
//
// Description
// -----------
// This method will add a new artist for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Artist to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_artistprofiles_artistAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'subname'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sub Name'),
        'sort_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sort Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'),
        'status'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Status'),
        'flags'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Options'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Primary Image'),
        'primary_image_caption'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Primary Image Caption'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        'setup_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Setup Image'),
        'setup_image_caption'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Setup Image Caption'),
        'setup_description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'checkAccess');
    $rc = ciniki_artistprofiles_checkAccess($ciniki, $args['tnid'], 'ciniki.artistprofiles.artistAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // If not specified, setup the sort name
    //
    if( !isset($args['sort_name']) || $args['sort_name'] == '' ) {
        $args['sort_name'] = $args['name'];
    }

    //
    // Setup permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
    }

    //
    // Make sure the permalink is unique
    //
    $strsql = "SELECT id, name, permalink "
        . "FROM ciniki_artistprofiles "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'event');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artistprofiles.16', 'msg'=>'You already have an artist with this name, please choose another name'));
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
    // Add the artist to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.artistprofiles.artist', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
        return $rc;
    }
    $artist_id = $rc['id'];

    //
    // Update the categories
    //
    if( isset($args['categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.artistprofiles', 'tag', $args['tnid'],
            'ciniki_artistprofiles_tags', 'ciniki_artistprofiles_history',
            'artist_id', $artist_id, 10, $args['categories']);
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.artistprofiles.artist', 'object_id'=>$artist_id));

    return array('stat'=>'ok', 'id'=>$artist_id);
}
?>
