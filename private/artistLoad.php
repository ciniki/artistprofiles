<?php
//
// Description
// ===========
// This function will return all the details for a artist profile.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_artistprofiles_artistLoad($ciniki, $business_id, $artist_id, $args) {


    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');

    //
    // Get the artist
    //
    $strsql = "SELECT ciniki_artistprofiles.id, "
        . "ciniki_artistprofiles.name, "
        . "ciniki_artistprofiles.sort_name, "
        . "ciniki_artistprofiles.permalink, "
        . "ciniki_artistprofiles.status, "
        . "ciniki_artistprofiles.flags, "
        . "ciniki_artistprofiles.primary_image_id, "
        . "ciniki_artistprofiles.primary_image_caption, "
        . "ciniki_artistprofiles.synopsis, "
        . "ciniki_artistprofiles.description, "
        . "ciniki_artistprofiles.setup_image_id, "
        . "ciniki_artistprofiles.setup_description "
        . "FROM ciniki_artistprofiles "
        . "WHERE ciniki_artistprofiles.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    if( is_numeric($artist_id) ) {
        $strsql .= "AND ciniki_artistprofiles.id = '" . ciniki_core_dbQuote($ciniki, $artist_id) . "' ";
    } else {
        $strsql .= "AND ciniki_artistprofiles.permalink = '" . ciniki_core_dbQuote($ciniki, $artist_id) . "' ";
    }
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artistprofiles', array(
        array('container'=>'artists', 'fname'=>'id', 'name'=>'artist',
            'fields'=>array('id', 'name', 'sort_name', 'permalink', 'status', 'flags', 'primary_image_id', 'primary_image_caption', 
                'synopsis', 'description', 'setup_image_id', 'setup_description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['artists'][0]['artist']) ) {
        return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'2859', 'msg'=>'Unable to find artist'));
    }
    $artist = $rc['artists'][0]['artist'];
    $artist_id = $artist['id']; // Incase this function was called with permalink

    $artist['categories'] = array();
    $artist['images'] = array();
    $artist['setupimages'] = array();
    $artist['links'] = array();
    $artist['audio'] = array();

    //
    // Get the categories
    //
    $strsql = "SELECT tag_type, tag_name "
        . "FROM ciniki_artistprofiles_tags "
        . "WHERE artist_id = '" . ciniki_core_dbQuote($ciniki, $artist_id) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND tag_type = 10 "
        . "ORDER BY tag_name "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.artistprofiles', array(
        array('container'=>'tags', 'fname'=>'tag_type', 'fields'=>array('tag_type', 'tag_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tags']) ) {
        foreach($rc['tags'] as $tag) {
            $artist['categories'][] = $tag['tag_name'];
        }
    }

    //
    // Get any images if requested
    //
    if( isset($args['images']) && ($args['images'] == 'yes' || $args['images'] == 'thumbs') ) {
        $strsql = "SELECT id, "
            . "(flags&0xF0) AS type, "
            . "name, flags, image_id, description "
            . "FROM ciniki_artistprofiles_images "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_artistprofiles_images.artist_id = '" . ciniki_core_dbQuote($ciniki, $artist_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.artistprofiles', array(
            array('container'=>'types', 'fname'=>'type', 
                'fields'=>array('type')),
            array('container'=>'images', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'flags', 'image_id', 'description')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['types']) ) {
            foreach($rc['types'] AS $type) {
                if( ($type['type']&0xF0) == 0 ) {
                    $artist['images'] = $type['images'];
                }
                if( ($type['type']&0x10) == 0x10 ) {
                    $artist['setupimages'] = $type['images'];
                }
            }
        }
        if( $args['images'] == 'thumbs' ) {
            if( isset($artist['images']) ) {
                foreach($artist['images'] as $img_id => $img) {
                    if( isset($img['image_id']) && $img['image_id'] > 0 ) {
                        $rc = ciniki_images_loadCacheThumbnail($ciniki, $business_id, $img['image_id'], 75);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                        $artist['images'][$img_id]['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                    }
                }
            }
            if( isset($artist['setupimages']) ) {
                foreach($artist['setupimages'] as $img_id => $img) {
                    if( isset($img['image_id']) && $img['image_id'] > 0 ) {
                        $rc = ciniki_images_loadCacheThumbnail($ciniki, $business_id, $img['image_id'], 75);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                        $artist['setupimages'][$img_id]['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                    }
                }
            }
        }
    }

    //
    // Get any audio if requested
    //
    if( isset($args['audio']) && $args['audio'] == 'yes' ) {
        $strsql = "SELECT id, name, permalink, sequence, flags, "
            . "mp3_audio_id, wav_audio_id, ogg_audio_id, description "
            . "FROM ciniki_artistprofiles_audio "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND artist_id = '" . ciniki_core_dbQuote($ciniki, $artist_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.artistprofiles', array(
            array('container'=>'audio', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'permalink', 'sequence', 'flags', 
                    'mp3_audio_id', 'wav_audio_id', 'ogg_audio_id', 'description')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['audio']) ) {
            $artist['audio'] = $rc['audio'];
        }
    }

    //
    // Get the video links for the profile
    //
    if( isset($args['videos']) && $args['videos'] == 'yes' ) {
        $strsql = "SELECT id, name, link_type, url, description "
            . "FROM ciniki_artistprofiles_links "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND artist_id = '" . ciniki_core_dbQuote($ciniki, $artist_id) . "' "
            . "AND link_type >= 2000 AND link_type < 3000 "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.blog', array(
            array('container'=>'links', 'fname'=>'id',
                'fields'=>array('id', 'name', 'link_type', 'url', 'description')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['links']) ) {
            $artist['videos'] = $rc['links'];
        } else {
            $artist['videos'] = array();
        }
    }

    //
    // Get any links if requested
    //
    if( isset($args['links']) && $args['links'] == 'yes' ) {
        $strsql = "SELECT id, name, link_type, url, description "
            . "FROM ciniki_artistprofiles_links "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND link_type < 2000 "
            . "AND artist_id = '" . ciniki_core_dbQuote($ciniki, $artist_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.artistprofiles', array(
            array('container'=>'links', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'link_type', 'url', 'description')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['links']) ) {
            $artist['links'] = $rc['links'];
        }
    }

    return array('stat'=>'ok', 'artist'=>$artist);
}
?>
