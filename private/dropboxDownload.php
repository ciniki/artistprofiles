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

    //
    // Check to make sure the dropbox flag is enabled for this business
    //
    if( !isset($ciniki['business']['modules']['ciniki.artistprofiles']['flags'])
        || ($ciniki['business']['modules']['ciniki.artistprofiles']['flags']&0x01) == 0 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2857', 'msg'=>'Dropbox integration not enabled'));
    }

    //
    // Get the categories available for a business
    //
    $strsql = "SELECT id, name, permalink "
        . "FROM ciniki_artistprofiles_tags "
        . "WHERE ciniki_artistprofiles_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artistprofiles', 'cat');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $ciniki_artist['categories'] = array();
    if( isset($rc['rows']) ) {
        foreach($rc['rows'] as $row) {
            $categories[$row['permalink']] = $row['id'];
        }
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
    $updates = array();
    $new_dropbox_cursor = $rc['cursor'];
    $entries = $rc['entries'];
    foreach($entries as $artist) {
        if( preg_match("#^($artistprofiles)/([^/]+)/([^/]+)/(info.rtf|info.txt|(primary_image|synopsis|description|images|audio|video|files)/(.*))$#", $artist[0], $matches) ) {
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
                            'path'=>$artist[1]['path'], 
                            'modified'=>$artist[1]['modified'], 
                            'mime_type'=>$artist[1]['mime_type'],
                            ); 
                        break;
                    case 'images': 
                    case 'audio': 
                    case 'video': 
                    case 'links': 
                        if( !isset($updates[$sort_name][$matches[5]]) ) {
                            $updates[$sort_name][$matches[5]] = array();
                        }
                        $updates[$sort_name][$matches[5]][] = array(
                            'path'=>$artist[1]['path'], 
                            'modified'=>$artist[1]['modified'], 
                            'mime_type'=>$artist[1]['mime_type'],
                            ); 
                        break;
                }
            } elseif( isset($matches[4]) && $matches[4] == 'info.txt' ) {
                $updates[$sort_name]['info'] = array(
                    'path'=>$artist[1]['path'], 
                    'modified'=>$artist[1]['modified'], 
                    'mime_type'=>$artist[1]['mime_type'],
                    ); 
            }
        }
    }

    //
    // Update Ciniki
    //
    foreach($updates as $sort_name => $artist) {
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
        $strsql = "SELECT id "
            . "FROM ciniki_artistprofiles_entries "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $sort_name) . "' "
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
            $permalink = ciniki_core_makePermalink($ciniki, $sort_name);
            $strsql = "SELECT id, name "
                . "FROM ciniki_artistprofiles_categories "
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $cargs['permalink']) . "' "
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
                'synopsis'=>'',
                'description'=>'',
                'url'=>'',
                'image_id'=>0,
                'images'=>array(),
                'audio'=>array(),
                'video'=>array(),
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
            $rc = ciniki_artistprofiles_artistLoad($ciniki, $business_id, $artist_id, array('images'=>'yes', 'audio'=>'yes', 'video'=>'yes', 'links'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                return $rc;
            }
            $ciniki_artist = $rc['artist'];

            //
            // Get the categories for the artist
            //
            $strsql = "SELECT ciniki_artistprofiles_categories.id, "
                . "ciniki_artistprofiles_categories.name, "
                . "ciniki_artistprofiles_categories.permalink "
                . "FROM ciniki_artistprofiles_category_entries, ciniki_artistprofiles_categories "
                . "WHERE ciniki_artistprofiles_category_entries.artist_id = '" . ciniki_core_dbQuote($ciniki, $artist_id) . "' "
                . "AND ciniki_artistprofiles_category_entries.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_artistprofiles_category_entries.category_id = ciniki_artistprofiles_categories.id "
                . "AND ciniki_artistprofiles_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artistprofiles', 'cat');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                return $rc;
            }
            $ciniki_artist['categories'] = array();
            if( isset($rc['rows']) ) {
                foreach($rc['rows'] as $row) {
                    $ciniki_artist['categories'][$row['permalink']] = $row['id'];
                }
            }
        }

        //
        // Decide what needs to be updated
        //
        $update_args = array();

        //
        // Go through the updated items
        //
        foreach($artist as $field => $details) {
            if( $field == 'info' || $field == 'info' ) {
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
                $lines = explode("\n", $content);
                foreach($lines as $line) {
                    $pieces = explode(":", $line);
                    if( $pieces[0] == 'name' ) {
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
                if( $rc['id'] != $ciniki_artist['image_id'] ) {
                    $update_args['image_id'] = $rc['id'];
                }
            }
            elseif( ($field == 'synopsis' || $field == 'description') && $details['mime_type'] == 'application/rtf' ) {
                $rc = ciniki_core_dropboxParseRTFToText($ciniki, $business_id, $client, $details['path']);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                    return $rc;
                }
                if( $rc['content'] != $ciniki_artist[$field] ) {
                    $update_args[$field] = $rc['content'];
                }
            }
            elseif( ($field == 'synopsis' || $field == 'description') && $details['mime_type'] == 'text/plain' ) {
                $rc = ciniki_core_dropboxOpenTXT($ciniki, $business_id, $client, $details['path']);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                    return $rc;
                }
                if( $rc['content'] != $ciniki_artist[$field] ) {
                    $update_args[$field] = $rc['content'];
                }
            }
            elseif( $field == 'images' ) {
                //
                // Load the extra images
                //
                foreach($details as $img) {
                    if( $img['mime_type'] == 'image/jpeg' ) {
                        $rc = ciniki_images_insertFromDropbox($ciniki, $business_id, $ciniki['session']['user']['id'], $client, $img['path'], 1, '', '', 'no');
                        if( $rc['stat'] != 'ok' && $rc['stat'] != 'exists' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                            return $rc;
                        }
                        $found = 'no';
                        if( isset($ciniki_artist['images']) ) {
                            foreach($ciniki_artist['images'] as $cimg) {
                                if( $cimg['image']['image_id'] == $rc['id'] ) {
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
                                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2856', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
                            }
                            $uuid = $rc['uuid'];
                            // Add object
                            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.artistprofiles.artist_image', array(
                                'uuid'=>$uuid,
                                'artist_id'=>$artist_id,
                                'name'=>'',
                                'permalink'=>$uuid,
                                'webflags'=>0,
                                'image_id'=>$image_id,
                                'description'=>'',
                                'url'=>'',
                                ), 0x04);
                            if( $rc['stat'] != 'ok' ) {
                                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                                return $rc;
                            }
                        }
                    }
                }
            }
        }

        //
        // Check categories
        //
        if( !isset($categories[$artist['category']]) ) {
            // Add category
            $permalink = ciniki_core_makePermalink($ciniki, $artist['category']);
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.artistprofiles.category', array(
                'name'=>$artist['category'],
                'permalink'=>$permalink), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                return $rc;
            }
            $categories[$permalink] = $rc['id'];
        }
        if( !isset($ciniki_artist['categories'][$artist['category']]) ) {
            // Add the category artist
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.artistprofiles.category_artist', array(
                'category_id'=>$categories[$artist['category']],
                'artist_id'=>$artist_id), 0x04);
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
