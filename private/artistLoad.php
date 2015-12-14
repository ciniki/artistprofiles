<?php
//
// Description
// ===========
// This function will return all the details for a directory entry.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_directory_artistLoad($ciniki, $business_id, $entry_id, $args) {

    //
    // Get the entry
    //
    $strsql = "SELECT ciniki_artistprofiles.id, "
        . "ciniki_artistprofiles.name, "
        . "ciniki_artistprofiles.sort_name, "
        . "ciniki_artistprofiles.image_id AS primary_image_id, "
        . "ciniki_artistprofiles.url, "
        . "ciniki_artistprofiles.synopsis, "
        . "ciniki_artistprofiles.description, "
        . "ciniki_artistprofiles.date_added, "
        . "ciniki_artistprofiles.last_updated, "
        . "ciniki_artistprofiles_images.id AS img_id, "
        . "ciniki_artistprofiles_images.name AS image_name, "
        . "ciniki_artistprofiles_images.webflags AS image_webflags, "
        . "ciniki_artistprofiles_images.image_id, "
        . "ciniki_artistprofiles_images.description AS image_description, "
        . "ciniki_artistprofiles_images.url AS image_url "
        . "FROM ciniki_artistprofiles "
        . "LEFT JOIN ciniki_artistprofiles_images ON ("
            . "ciniki_artistprofiles.id = ciniki_artistprofiles_images.entry_id "
            . "AND ciniki_artistprofiles_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_artistprofiles.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_artistprofiles.id = '" . ciniki_core_dbQuote($ciniki, $entry_id) . "' "
        . "";
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
        array('container'=>'entries', 'fname'=>'id', 'name'=>'entry',
            'fields'=>array('id', 'name', 'sort_name', 'image_id'=>'primary_image_id', 
                'url', 'synopsis', 'description')),
        array('container'=>'images', 'fname'=>'img_id', 'name'=>'image',
            'fields'=>array('id'=>'img_id', 'name'=>'image_name', 'webflags'=>'image_webflags',
                'image_id', 'description'=>'image_description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['entries']) ) {
        return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'2859', 'msg'=>'Unable to find entry'));
    }
    $entry = array_pop($rc['entries']);
    $entry = $entry['entry'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
    if( isset($entry['images']) ) {
        foreach($entry['images'] as $img_id => $img) {
            if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
                $rc = ciniki_images_loadCacheThumbnail($ciniki, $business_id, $img['image']['image_id'], 75);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $entry['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
            }
        }
    }

    //
    // Get the categories the entry is attached to
    //
    $strsql = "SELECT 'cat' AS type, name AS lists "
        . "FROM ciniki_directory_category_entries, ciniki_directory_categories "
        . "WHERE ciniki_directory_category_entries.entry_id = '" . ciniki_core_dbQuote($ciniki, $entry_id) . "' "
        . "AND ciniki_directory_category_entries.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_directory_category_entries.category_id = ciniki_directory_categories.id "
        . "AND ciniki_directory_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ". "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
        array('container'=>'categories', 'fname'=>'type', 'name'=>'categories',
            'fields'=>array('lists'), 'dlists'=>array('lists'=>'::')),
            ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories'][0]['categories']['lists']) ) {
        $entry['categories'] = $rc['categories'][0]['categories']['lists'];
    } else {
        $entry['categories'] = '';
    }

    //
    // Get any files if requested
    //
    if( isset($args['files']) && $args['files'] == 'yes' ) {
        $strsql = "SELECT id, name, extension, permalink "
            . "FROM ciniki_directory_entry_files "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_directory_entry_files.entry_id = '" . ciniki_core_dbQuote($ciniki, $entry_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
            array('container'=>'files', 'fname'=>'id', 'name'=>'file',
                'fields'=>array('id', 'name', 'extension', 'permalink')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['files']) ) {
            $entry['files'] = $rc['files'];
        }
    }

    //
    // Check if sponsors was requested
    //
    if( isset($args['files']) && $args['files'] == 'yes' 
        && isset($ciniki['business']['modules']['ciniki.sponsors'])
        && ($ciniki['business']['modules']['ciniki.sponsors']['flags']&0x02) == 0x02
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sponsors', 'hooks', 'sponsorList');
        $rc = ciniki_sponsors_hooks_sponsorList($ciniki, $args['business_id'], 
            array('object'=>'ciniki.directory.entry', 'object_id'=>$entry_id));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['sponsors']) ) {
            $entry['sponsors'] = $rc['sponsors'];
        }
    }

    //
    // Check if the list of categories should be included in the result
    //
    if( isset($args['categories']) && $args['categories'] == 'yes' ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_directory_categories "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.directory', array(
            array('container'=>'categories', 'fname'=>'id', 'name'=>'category',
                'fields'=>array('id', 'name'))
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        return array('stat'=>'ok', 'categories'=>$rc['categories'], 'entry'=>$entry);
    }

    return array('stat'=>'ok', 'entry'=>$entry);
}
?>
