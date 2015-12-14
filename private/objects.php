<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_artistprofiles_objects($ciniki) {
    
    $objects = array();
    $objects['artist'] = array(
        'name'=>'Artist',
        'sync'=>'yes',
        'table'=>'ciniki_artistprofiles',
        'fields'=>array(
            'name'=>array(),
            'permalink'=>array(),
            'primary_image_id'=>array('ref'=>'ciniki.images.image'),
            'synopsis'=>array(),
            'description'=>array(),
            ),
        'history_table'=>'ciniki_artistprofiles_history',
        );
    $objects['image'] = array(
        'name'=>'Image',
        'sync'=>'yes',
        'table'=>'ciniki_artistprofiles_images',
        'fields'=>array(
            'artist_id'=>array('ref'=>'ciniki.artistprofiles.artist'),
            'name'=>array(),
            'permalink'=>array(),
            'webflags'=>array(),
            'image_id'=>array('ref'=>'ciniki.images.image'),
            'description'=>array(),
            'url'=>array(),
            ),
        'history_table'=>'ciniki_artistprofiles_history',
        );
    $objects['tag'] = array(
        'name'=>'Tag',
        'sync'=>'yes',
        'table'=>'ciniki_artistprofiles_tags',
        'fields'=>array(
            'artist_id'=>array('ref'=>'ciniki.artistprofiles.artist'),
            'tag_type'=>array(),
            'tag_name'=>array(),
            'permalink'=>array(),
            ),
        'history_table'=>'ciniki_artistprofiles_history',
        );
    $objects['link'] = array(
        'name'=>'Link',
        'sync'=>'yes',
        'table'=>'ciniki_artistprofiles_links',
        'fields'=>array(
            'artist_id'=>array('ref'=>'ciniki.artistprofiles.artist'),
            'name'=>array(),
            'url'=>array(),
            'description'=>array(),
            ),
        'history_table'=>'ciniki_artistprofiles_history',
        );
    $objects['setting'] = array(
        'type'=>'settings',
        'name'=>'Event Settings',
        'table'=>'ciniki_artistprofiles_settings',
        'history_table'=>'ciniki_artistprofiles_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
