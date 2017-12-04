<?php
//
// Description
// -----------
// This function returns the index details for an object
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_artistprofiles_hooks_webIndexObject($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artistprofiles.2', 'msg'=>'No object specified'));
    }

    if( !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artistprofiles.3', 'msg'=>'No object ID specified'));
    }

    //
    // Setup the base_url for use in index
    //
    if( isset($args['base_url']) ) {
        $base_url = $args['base_url'];
    } else {
        $base_url = '/artists';
    }

    if( $args['object'] == 'ciniki.artistprofiles.artist' ) {
        $strsql = "SELECT id, name, subname, sort_name, permalink, status, "
            . "primary_image_id, synopsis, description "
            . "FROM ciniki_artistprofiles "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artistprofiles', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artistprofiles.4', 'msg'=>'Object not found'));
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.artistprofiles.5', 'msg'=>'Object not found'));
        }

        //
        // Check if item is visible on website
        //
        if( $rc['item']['status'] != '10' ) {
            return array('stat'=>'ok');
        }
        $object = array(
            'label'=>'Artists',
            'title'=>$rc['item']['name'],
            'subtitle'=>$rc['item']['subname'],
            'meta'=>'',
            'primary_image_id'=>$rc['item']['primary_image_id'],
            'synopsis'=>$rc['item']['synopsis'],
            'object'=>'ciniki.artistprofiles.artist',
            'object_id'=>$rc['item']['id'],
            'primary_words'=>$rc['item']['name'],
            'secondary_words'=>$rc['item']['subname'] . ' ' . $rc['item']['synopsis'],
            'tertiary_words'=>$rc['item']['description'],
            'weight'=>20000,
            'url'=>$base_url . '/' . $rc['item']['permalink']
            );
        return array('stat'=>'ok', 'object'=>$object);
    }

    return array('stat'=>'ok');
}
?>
