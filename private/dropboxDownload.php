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
require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/dropbox/lib/Dropbox/autoload.php');
use \Dropbox as dbx;

function ciniki_artistprofiles_dropboxDownload(&$ciniki, $business_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'insertFromDropbox');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dropboxParseRTFToText');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dropboxOpenTXT');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'artistLoad');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'dropboxDownloadImages');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'dropboxDownloadLinks');

    //
    // Check to make sure the dropbox flag is enabled for this business
    //
    if( !isset($ciniki['business']['modules']['ciniki.artistprofiles']['flags'])
        || ($ciniki['business']['modules']['ciniki.artistprofiles']['flags']&0x01) == 0 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2857', 'msg'=>'Dropbox integration not enabled'));
    }

    //
    // Get the settings for artistprofiles
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_artistprofiles_settings', 
        'business_id', $business_id, 'ciniki.artistprofiles', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']['dropbox-artistprofiles']) || $rc['settings']['dropbox-artistprofiles'] == '') {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2853', 'msg'=>'Dropbox artistprofiles not setup.'));
    }
    $artistprofiles = $rc['settings']['dropbox-artistprofiles'];
    if( $artistprofiles[0] != '/' ) {
        $artistprofiles = '/' . $artistprofiles;
    }
    rtrim($artistprofiles, '/');
    $dropbox_cursor = null;
    if( isset($rc['settings']['dropbox-cursor']) && $rc['settings']['dropbox-cursor'] != '') {
        $dropbox_cursor = $rc['settings']['dropbox-cursor'];
    }

    //
    // Check if we should ignore the old cursor and start from scratch
    //
    if( isset($ciniki['config']['ciniki.artistprofiles']['ignore.cursor']) 
        && ($ciniki['config']['ciniki.artistprofiles']['ignore.cursor'] == 1 || $ciniki['config']['ciniki.artistprofiles']['ignore.cursor'] == 'yes') 
        ) {
        $dropbox_cursor = null;
    }

    //
    // Get the settings for dropbox
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_business_details', 
        'business_id', $business_id, 'ciniki.businesses', 'settings', 'apis');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']['apis-dropbox-access-token']) 
        || $rc['settings']['apis-dropbox-access-token'] == ''
        ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2854', 'msg'=>'Dropbox not configured.'));
    }
    $access_token = $rc['settings']['apis-dropbox-access-token'];

    $client = new dbx\Client($access_token, 'Ciniki');

    //
    // Get the latest changes from Dropbox
    //
    $rc = $client->getDelta($dropbox_cursor, $artistprofiles);
    if( !isset($rc['entries']) ) {
        // Nothing to update, return
        return array('stat'=>'ok');
    }
    // If there is more
    $dropbox_cursor = $rc['cursor'];
    if( count($rc['entries']) == 0 && $rc['has_more'] == 1 ) {
        error_log('delta again');
        $rc = $client->getDelta($dropbox_cursor, $artistprofiles);
        if( !isset($rc['entries']) ) {
            // Nothing to update, return
            return array('stat'=>'ok');
        }
    }
    $updates = array();
    $new_dropbox_cursor = $rc['cursor'];
    $entries = $rc['entries'];
    foreach($entries as $entry) {
        //
        // Entries look like:
        //      [0] => /website/artists/canada/rivett-andrew/primary_image/img_0610.jpg
        //      [1] => Array
        //          (
        //              [rev] => 230d1f249e
        //              [thumb_exists] => 1
        //              [path] => /website/artists/canada/rivett-andrew/primary_image/IMG_0610.jpg
        //              [is_dir] =>
        //              [client_mtime] => Wed, 15 Jan 2014 13:37:06 +0000
        //              [icon] => page_white_picture
        //              [read_only] =>
        //              [modifier] =>
        //              [bytes] => 114219
        //              [modified] => Sat, 14 Mar 2015 19:23:45 +0000
        //              [size] => 111.5 KB
        //              [root] => dropbox
        //              [mime_type] => image/jpeg
        //              [revision] => 35
        //          )
        //
        // Check for a match in the specified directory and path matches valid path list information
        //
        if( preg_match("#^($artistprofiles)/([^/]+)/([^/]+)/(info.rtf|info.txt|(primary_image|synopsis|description|audio|images|links)/(.*))$#", $entry[0], $matches) ) {
            $sort_name = $matches[3];
            if( !isset($updates[$sort_name]) ) {
                // Create an artist in updates, with the category permalink
                $updates[$sort_name] = array('category'=>$matches[2]);
            }
            if( isset($matches[5]) ) {
                switch($matches[5]) {
                    case 'primary_image': 
                    case 'synopsis': 
                    case 'description': 
                        $updates[$sort_name][$matches[5]] = array(
                            'path'=>$entry[1]['path'], 
                            'modified'=>$entry[1]['modified'], 
                            'mime_type'=>$entry[1]['mime_type'],
                            ); 
                        break;
                    case 'images': 
                    case 'audio': 
                    case 'links': 
                        if( !isset($updates[$sort_name][$matches[5]]) ) {
                            $updates[$sort_name][$matches[5]] = array();
                        }
                        print_r($entry);
                        $updates[$sort_name][$matches[5]][] = array(
                            'path'=>$entry[0], 
                            'filename'=>$entry[1]['path'],
                            'modified'=>$entry[1]['modified'], 
                            'mime_type'=>$entry[1]['mime_type'],
                            ); 
                        break;
                }
            } elseif( isset($matches[4]) && $matches[4] == 'info.txt' ) {
                $updates[$sort_name]['info'] = array(
                    'path'=>$entry[1]['path'], 
                    'modified'=>$entry[1]['modified'], 
                    'mime_type'=>$entry[1]['mime_type'],
                    ); 
            }
        }
    }

    //
    // Update Ciniki
    //
    foreach($updates as $sort_name => $artist) {
        error_log("Updating: " . $sort_name);

        //  
        // Turn off autocommit
        //  
        $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.artistprofiles');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }   
        
        //
        // Lookup the artist in the artistprofiles
        //
        $permalink = ciniki_core_makePermalink($ciniki, $sort_name);
        $strsql = "SELECT id "
            . "FROM ciniki_artistprofiles "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artistprofiles', 'artist');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
            return $rc;
        }

        //
        // Add artist
        //
        if( !isset($rc['artist']) && $rc['num_rows'] == 0 ) {
            //
            // Check permalink doesn't already exist
            //
            $strsql = "SELECT id, name "
                . "FROM ciniki_artistprofiles "
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artistprofiles', 'item');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                return $rc;
            }
            if( isset($rc['num_rows']) && $rc['num_rows'] > 0 ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2855', 'msg'=>'Directory artist already exists for ' . $sort_name));
            }
            
            // 
            // Add the artist
            //
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.artistprofiles.artist', array(
                'name'=>$sort_name,
                'sort_name'=>$sort_name,
                'permalink'=>$permalink, 
                'status'=>'10',
                'flags'=>'0',
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                return $rc;
            }
            $artist_id = $rc['id'];
            $ciniki_artist = array(
                'id'=>$artist_id,
                'name'=>$sort_name,
                'sort_name'=>$sort_name,
                'permalink'=>$permalink,
                'status'=>10,
                'flags'=>0,
                'primary_image_id'=>0,
                'synopsis'=>'',
                'description'=>'',
                'setup_image_id'=>0,
                'setup_description'=>'',
                'audio'=>array(),
                'images'=>array(),
                'links'=>array(),
                'categories'=>array(),
                );
        } 
    
        //
        // Load the full artist
        //
        else {
            $artist_id = $rc['artist']['id'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'artistLoad');
            $rc = ciniki_artistprofiles_artistLoad($ciniki, $business_id, $artist_id, array('images'=>'yes', 'audio'=>'yes', 'links'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                return $rc;
            }
            $ciniki_artist = $rc['artist'];
        }

        //
        // Decide what needs to be updated
        //
        $update_args = array();

        //
        // Go through the updated items
        //
        foreach($artist as $field => $details) {
            if( $field == 'info' ) {
                print "info.txt: " . $details['path'] . "\n";
                if( $details['mime_type'] == 'text/plain' ) {
                    $rc = ciniki_core_dropboxOpenTXT($ciniki, $business_id, $client, $details['path']);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                        return $rc;
                    }
                    $content = $rc['content'];
                } elseif( $details['mime_type'] == 'application/rtf' ) {
                    $rc = ciniki_core_dropboxParseRTFToText($ciniki, $business_id, $client, $details['path']);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                        return $rc;
                    }
                    $content = $rc['content'];
                }
                print "info.txt:\n";
                print_r($content);
                print "\n";
                $lines = explode("\n", $content);
                foreach($lines as $line) {
                    $pieces = explode(":", $line);
                    if( isset($pieces[1]) && stristr($pieces[0], 'name') !== FALSE ) {
                        $name = rtrim(ltrim($pieces[1]));
                        if( $name != $ciniki_artist['name'] ) {
                            $update_args['name'] = $name;
                        }
                    }
                }
            }
            elseif( $field == 'primary_image' && $details['mime_type'] == 'image/jpeg' ) {
                $rc = ciniki_images_insertFromDropbox($ciniki, $business_id, $ciniki['session']['user']['id'], $client, $details['path'], 1, '', '', 'no');
                if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                    return $rc;
                }
                if( $rc['id'] != $ciniki_artist['primary_image_id'] ) {
                    $update_args['primary_image_id'] = $rc['id'];
                }
            }
            elseif( $field == 'setup_image' && $details['mime_type'] == 'image/jpeg' ) {
                $rc = ciniki_images_insertFromDropbox($ciniki, $business_id, $ciniki['session']['user']['id'], $client, $details['path'], 1, '', '', 'no');
                if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                    return $rc;
                }
                if( $rc['id'] != $ciniki_artist['setup_image_id'] ) {
                    $update_args['setup_image_id'] = $rc['id'];
                }
            }
            elseif( ($field == 'synopsis' || $field == 'description' || $field == 'setup_description' ) && $details['mime_type'] == 'application/rtf' ) {
                $rc = ciniki_core_dropboxParseRTFToText($ciniki, $business_id, $client, $details['path']);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                    return $rc;
                }
                if( $rc['content'] != $ciniki_artist[$field] ) {
                    $update_args[$field] = $rc['content'];
                }
            }
            elseif( ($field == 'synopsis' || $field == 'description' || $field == 'setup_description' ) && $details['mime_type'] == 'text/plain' ) {
                $rc = ciniki_core_dropboxOpenTXT($ciniki, $business_id, $client, $details['path']);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                    return $rc;
                }
                if( $rc['content'] != $ciniki_artist[$field] ) {
                    $update_args[$field] = $rc['content'];
                }
            }
            elseif( $field == 'images' || $field == 'setupimages' ) {
                $rc = ciniki_artistprofiles_dropboxDownloadImages($ciniki, $business_id, $client, $ciniki_artist, $details);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                    return $rc;
                }
            }
            elseif( $field == 'links' ) {
                $rc = ciniki_artistprofiles_dropboxDownloadLinks($ciniki, $business_id, $client, $ciniki_artist, $details);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                    return $rc;
                }
            }
        }

        //
        // Check categories
        //
        if( !in_array($artist['category'], $ciniki_artist['categories']) ) {
            $permalink = ciniki_core_makePermalink($ciniki, $artist['category']);
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.artistprofiles.tag', array(
                'artist_id'=>$artist_id,
                'tag_type'=>10,
                'tag_name'=>$artist['category'],
                'permalink'=>$permalink), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                return $rc;
            }
        }
            
        //
        // Update the artist
        //
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.artistprofiles.artist', $artist_id, $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                return $rc;
            }
        }

        //  
        // Commit the changes
        //  
        $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.artistprofiles');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }   
    }

    //
    // Update the dropbox cursor
    //
    $strsql = "INSERT INTO ciniki_artistprofiles_settings (business_id, detail_key, detail_value, date_added, last_updated) "
        . "VALUES ('" . ciniki_core_dbQuote($ciniki, $business_id) . "'"
        . ", '" . ciniki_core_dbQuote($ciniki, 'dropbox-cursor') . "'"
        . ", '" . ciniki_core_dbQuote($ciniki, $new_dropbox_cursor) . "'"
        . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
        . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $new_dropbox_cursor) . "' "
        . ", last_updated = UTC_TIMESTAMP() "
        . "";
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.artistprofiles');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
        return $rc;
    }
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.artistprofiles', 'ciniki_artistprofiles_history', $business_id, 
        2, 'ciniki_artistprofiles_settings', 'dropbox-cursor', 'detail_value', $new_dropbox_cursor);
    $ciniki['syncqueue'][] = array('push'=>'ciniki.artistprofiles.setting', 
        'args'=>array('id'=>'dropbox-cursor'));

    return array('stat'=>'ok');
}
?>
