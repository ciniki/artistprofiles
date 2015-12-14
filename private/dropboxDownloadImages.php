<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
//require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/dropbox/lib/Dropbox/autoload.php');
//use \Dropbox as dbx;

function ciniki_artistprofiles_dropboxDownloadImages(&$ciniki, $business_id, $client, $artist, $details) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dropboxOpenWebloc');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');


    foreach($details as $img) {
        print_r($img);
        $flags = 0x01;  // Visible on website
        if( $img['mime_type'] == 'image/jpeg' ) {
            $rc = ciniki_images_insertFromDropbox($ciniki, $business_id, $ciniki['session']['user']['id'], $client, $img['path'], 1, '', '', 'no');
            if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
                return $rc;
            }
            $found = 'no';
            if( isset($ciniki_artist['images']) ) {
                foreach($ciniki_artist['images'] as $artist_img) {
                    if( $artist_img['image_id'] == $rc['id'] ) {
                        $found = 'yes';
                        break;
                    }
                }
            }
            
            if( $found == 'no' ) {
                $image_id = $rc['id'];
                // Get UUID
                $rc = ciniki_core_dbUUID($ciniki, 'ciniki.artistprofiles');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2856', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
                }
                $uuid = $rc['uuid'];
                // Add object
                $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.artistprofiles.image', array(
                    'uuid'=>$uuid,
                    'artist_id'=>$artist['id'],
                    'name'=>'',
                    'permalink'=>$uuid,
                    'flags'=>$flags,
                    'image_id'=>$image_id,
                    'description'=>'',
                    ), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
