<?php
//
// Description
// ===========
// This method will return all the information about an artist.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the artist is attached to.
// artist_id:          The ID of the artist to get the details for.
//
// Returns
// -------
//
function ciniki_artistprofiles_artistGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'artist_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Artist'),
        'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
        'links'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Links'),
        'videos'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Videos'),
        'audio'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Audio'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'checkAccess');
    $rc = ciniki_artistprofiles_checkAccess($ciniki, $args['tnid'], 'ciniki.artistprofiles.artistGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load artist profile maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'maps');
    $rc = ciniki_artistprofiles_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Return default for new Artist
    //
    if( $args['artist_id'] == 0 ) {
        $artist = array('id'=>0,
            'name'=>'',
            'subname'=>'',
            'sort_name'=>'',
            'permalink'=>'',
            'status'=>'',
            'flags'=>'',
            'primary_image_id'=>'0',
            'primary_image_caption'=>'',
            'synopsis'=>'',
            'description'=>'',
            'setup_image_id'=>'0',
            'setup_image_caption'=>'',
            'setup_description'=>'',
        );
    }

    //
    // Get the details for an existing Artist
    //
    else {
        $strsql = "SELECT ciniki_artistprofiles.id, "
            . "ciniki_artistprofiles.name, "
            . "ciniki_artistprofiles.subname, "
            . "ciniki_artistprofiles.sort_name, "
            . "ciniki_artistprofiles.permalink, "
            . "ciniki_artistprofiles.status, "
            . "ciniki_artistprofiles.status AS status_text, "
            . "ciniki_artistprofiles.flags, "
            . "ciniki_artistprofiles.flags AS flags_text, "
            . "ciniki_artistprofiles.primary_image_id, "
            . "ciniki_artistprofiles.primary_image_caption, "
            . "ciniki_artistprofiles.synopsis, "
            . "ciniki_artistprofiles.description, "
            . "ciniki_artistprofiles.setup_image_id, "
            . "ciniki_artistprofiles.setup_image_caption, "
            . "ciniki_artistprofiles.setup_description "
            . "FROM ciniki_artistprofiles "
            . "WHERE ciniki_artistprofiles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_artistprofiles.id = '" . ciniki_core_dbQuote($ciniki, $args['artist_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artistprofiles', array(
            array('container'=>'artists', 'fname'=>'id', 'name'=>'artist',
                'fields'=>array('id', 'name', 'subname', 'sort_name', 'permalink', 'status', 'status_text',
                    'flags', 'flags_text', 'primary_image_id', 'primary_image_caption', 'synopsis', 'description',
                    'setup_image_id', 'setup_image_caption', 'setup_description'),
                 'maps'=>array('status_text'=>$maps['artist']['status']),
                 'flags'=>array('flags_text'=>$maps['artist']['flags']),
                 ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artistprofiles.18', 'msg'=>'Artist not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['artists'][0]['artist']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artistprofiles.19', 'msg'=>'Unable to find Artist'));
        }
        $artist = $rc['artists'][0]['artist'];

        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

        //
        // Get the categories
        //
        $strsql = "SELECT tag_type, tag_name AS lists "
            . "FROM ciniki_artistprofiles_tags "
            . "WHERE artist_id = '" . ciniki_core_dbQuote($ciniki, $args['artist_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY tag_type, tag_name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.artistprofiles', array(
            array('container'=>'tags', 'fname'=>'tag_type', 'name'=>'tags',
                'fields'=>array('tag_type', 'lists'), 'dlists'=>array('lists'=>'::')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['tags']) ) {
            foreach($rc['tags'] as $tags) {
                if( $tags['tags']['tag_type'] == 10 ) {
                    $artist['categories'] = $tags['tags']['lists'];
                }
            }
        }

        //
        // Get the images
        //
        if( isset($args['images']) && $args['images'] == 'yes' ) {
            $strsql = "SELECT id, "
                . "name, "
                . "flags, "
                . "image_id, "
                . "description "
                . "FROM ciniki_artistprofiles_images "
                . "WHERE artist_id = '" . ciniki_core_dbQuote($ciniki, $args['artist_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.artistprofiles', array(
                array('container'=>'images', 'fname'=>'id', 
                    'fields'=>array('id', 'name', 'flags', 'image_id', 'description')),
            ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['images']) ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
                $artist['images'] = $rc['images'];
                foreach($artist['images'] as $img_id => $img) {
                    if( isset($img['image_id']) && $img['image_id'] > 0 ) {
                        $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['tnid'], $img['image_id'], 75);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                        $artist['images'][$img_id]['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                    }
                }
            } else {
                $artist['images'] = array();
            }
        }

        //
        // Get the links for the profile
        //
        if( isset($args['links']) && $args['links'] == 'yes' ) {
            $strsql = "SELECT id, name, link_type, url, description "
                . "FROM ciniki_artistprofiles_links "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_artistprofiles_links.artist_id = '" . ciniki_core_dbQuote($ciniki, $args['artist_id']) . "' "
                . "AND link_type >= 1000 AND link_type < 2000 "
                . "ORDER BY sequence "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.blog', array(
                array('container'=>'links', 'fname'=>'id',
                    'fields'=>array('id', 'name', 'url', 'description')),
            ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['links']) ) {
                $artist['links'] = $rc['links'];
            } else {
                $artist['links'] = array();
            }
        }

        //
        // Get the video links for the profile
        //
        if( isset($args['videos']) && $args['videos'] == 'yes' ) {
            $strsql = "SELECT id, name, link_type, url, description "
                . "FROM ciniki_artistprofiles_links "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND artist_id = '" . ciniki_core_dbQuote($ciniki, $args['artist_id']) . "' "
                . "AND link_type >= 2000 AND link_type < 3000 "
                . "ORDER BY sequence "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.blog', array(
                array('container'=>'links', 'fname'=>'id',
                    'fields'=>array('id', 'name', 'url', 'description')),
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
    }

    $rsp = array('stat'=>'ok', 'artist'=>$artist);

    //
    // Check if all tags should be returned
    //
    $rsp['categories'] = array();
    if( ($ciniki['tenant']['modules']['ciniki.artistprofiles']['flags']&0x0100) > 0
        && isset($args['categories']) && $args['categories'] == 'yes' 
        ) {
        //
        // Get the available tags
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsList');
        $rc = ciniki_core_tagsList($ciniki, 'ciniki.artistprofiles', $args['tnid'], 'ciniki_artistprofiles_tags', 10);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artistprofiles.20', 'msg'=>'Unable to get list of categories', 'err'=>$rc['err']));
        }
        if( isset($rc['tags']) ) {
            $rsp['categories'] = $rc['tags'];
        }
    }

    return $rsp;
}
?>
