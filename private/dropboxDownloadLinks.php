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

function ciniki_artistprofiles_dropboxDownloadLinks(&$ciniki, $business_id, $client, $artist, $details) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dropboxOpenWebloc');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'linkType');

    foreach($details as $link) {
        $name = '';
        $url = '';
        $description = '';
        if( preg_match('/^.*\/([^\/]*)\.webloc$/', $link['filename'], $matches) ) {
            $name = $matches[1];
            $rc = ciniki_core_dropboxOpenWebloc($ciniki, $business_id, $client, $link['path']);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                return $rc;
            }
            $url = $rc['url'];
        }

        if( $url != '' ) {
            $found = 'no';
            $found_link = null;
            
            $link_type = 1000;
            $rc = ciniki_artistprofiles_linkType($ciniki, $business_id, $url);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $link_type = $rc['link_type'];

            foreach($artist['links'] as $artist_link) {
                if( $artist_link['url'] == $url && $artist_link['link_type'] == $link_type ) {
                    $found = 'yes';
                    $found_link = $artist_link;
                    break;
                }
            }
            foreach($artist['videos'] as $artist_link) {
                if( $artist_link['url'] == $url && $artist_link['link_type'] == $link_type ) {
                    $found = 'yes';
                    $found_link = $artist_link;
                    break;
                }
            }
            if( $found == 'no' ) {
                $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.artistprofiles.link', array(
                    'artist_id'=>$artist['id'],
                    'name'=>$name,
                    'link_type'=>$link_type,
                    'url'=>$url,
                    'description'=>$description,
                    ), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                    return $rc;
                }
            } elseif( $found == 'yes' ) {
                $update_args = array();
                if( $found_link['url'] != $url ) {
                    $update_args['url'] = $url;
                }
                if( $found_link['name'] != $name ) {
                    $update_args['name'] = $name;
                }
                if( count($update_args) > 0 ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.artistprofiles.link', $found_link['id'], $update_args, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                        return $rc;
                    }
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
