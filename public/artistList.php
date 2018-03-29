<?php
//
// Description
// -----------
// This method will return the list of Artists for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Artist for.
//
// Returns
// -------
//
function ciniki_artistprofiles_artistList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'category_permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'thumbnails'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Thumbnails'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'checkAccess');
    $rc = ciniki_artistprofiles_checkAccess($ciniki, $args['tnid'], 'ciniki.artistprofiles.artistList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of artists
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.artistprofiles', 0x0100) 
        && isset($args['category_permalink']) && $args['category_permalink'] != '' 
        ) {
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.artistprofiles', 0x0200) && $args['category_permalink'] == 'featured' ) {
            $strsql = "SELECT ciniki_artistprofiles.id, "
                . "ciniki_artistprofiles.name, "
                . "ciniki_artistprofiles.sort_name, "
                . "ciniki_artistprofiles.permalink, "
                . "ciniki_artistprofiles.status, "
                . "ciniki_artistprofiles.flags, "
                . "ciniki_artistprofiles.primary_image_id, "
                . "IFNULL(images.id, 0) AS i_id, "
                . "IFNULL(images.image_id, 0) AS image_id "
                . "FROM ciniki_artistprofiles "
                . "LEFT JOIN ciniki_artistprofiles_images AS images ON ("
                    . "ciniki_artistprofiles.id = images.artist_id "
                    . "AND images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE ciniki_artistprofiles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND (ciniki_artistprofiles.flags&0x01) = 0x01 "
                . "ORDER BY ciniki_artistprofiles.sort_name "
                . "";
        } else {
            $strsql = "SELECT ciniki_artistprofiles.id, "
                . "ciniki_artistprofiles.name, "
                . "ciniki_artistprofiles.sort_name, "
                . "ciniki_artistprofiles.permalink, "
                . "ciniki_artistprofiles.status, "
                . "ciniki_artistprofiles.flags, "
                . "ciniki_artistprofiles.primary_image_id, "
                . "IFNULL(images.id, 0) AS i_id, "
                . "IFNULL(images.image_id, 0) AS image_id "
                . "FROM ciniki_artistprofiles_tags "
                . "INNER JOIN ciniki_artistprofiles ON ("
                    . "ciniki_artistprofiles_tags.artist_id = ciniki_artistprofiles.id "
                    . "AND ciniki_artistprofiles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_artistprofiles_images AS images ON ("
                    . "ciniki_artistprofiles.id = images.artist_id "
                    . "AND images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE ciniki_artistprofiles_tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_artistprofiles_tags.tag_type = 10 "
                . "AND ciniki_artistprofiles_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category_permalink']) . "' "
                . "ORDER BY ciniki_artistprofiles.sort_name "
                . "";
        }
    } else {
        $strsql = "SELECT ciniki_artistprofiles.id, "
            . "ciniki_artistprofiles.name, "
            . "ciniki_artistprofiles.sort_name, "
            . "ciniki_artistprofiles.permalink, "
            . "ciniki_artistprofiles.status, "
            . "ciniki_artistprofiles.flags, "
            . "ciniki_artistprofiles.primary_image_id, "
            . "IFNULL(images.id, 0) AS i_id, "
            . "IFNULL(images.image_id, 0) AS image_id "
            . "FROM ciniki_artistprofiles "
            . "LEFT JOIN ciniki_artistprofiles_images AS images ON ("
                . "ciniki_artistprofiles.id = images.artist_id "
                . "AND images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_artistprofiles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_artistprofiles.sort_name "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.artistprofiles', array(
        array('container'=>'artists', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'sort_name', 'permalink', 'status', 'flags', 'primary_image_id')),
        array('container'=>'images', 'fname'=>'image_id', 'fields'=>array('id'=>'i_id', 'image_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['artists']) ) {
        $artists = $rc['artists'];
    } else {
        $artists = array();
    }

    $rsp = array('stat'=>'ok', 'artists'=>$artists);

    //
    // Get the thumbnails 
    //
    if( isset($args['thumbnails']) && $args['thumbnails'] == 'yes' ) {
        $images = array();
        //
        // Build the array of images
        //
        foreach($artists as $artist) {
            if( $artist['primary_image_id'] > 0 ) {
                $images[] = array(
                    'id' => $artist['primary_image_id'],
                    'image_id' => $artist['primary_image_id'],
                    );
            }
            if( isset($artist['images']) ) {
                foreach($artist['images'] as $image) {
                    if( $image['image_id'] > 0 ) {
                        $images[] = array(
                            'id' => $image['image_id'],
                            'image_id' => $image['image_id'],
                            );
                    }
                }
            }
        }
        //
        // Add the thumbnails
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
        foreach($images as $img_id => $img) {
            if( isset($img['image_id']) && $img['image_id'] > 0 ) {
                $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['tnid'], $img['image_id'], 75);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $images[$img_id]['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
            }
        }
        $rsp['thumbnails'] = $images;
    }

    //
    // Return list of categories
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.artistprofiles', 0x0100) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'web', 'subMenuItems');
        $rc = ciniki_artistprofiles_web_subMenuItems($ciniki, array(), $args['tnid'], array());
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['categories'] = $rc['submenu'];
    }

    return $rsp;
}
?>
