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
        'o_name'=>'artist',
        'o_container'=>'artists',
        'sync'=>'yes',
        'table'=>'ciniki_artistprofiles',
        'fields'=>array(
            'name'=>array('name'=>'Name'),
            'sort_name'=>array('name'=>'Sort Name'),
            'permalink'=>array('name'=>'Permalink'),
            'status'=>array('name'=>'Status'),
            'flags'=>array('name'=>'Options'),
            'primary_image_id'=>array('name'=>'Primary Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'primary_image_caption'=>array('name'=>'Primary Image Caption', 'ref'=>'ciniki.images.image', 'default'=>''),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            'setup_image_id'=>array('name'=>'Setup Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'setup_image_caption'=>array('name'=>'Setup Image Caption', 'ref'=>'ciniki.images.image', 'default'=>''),
            'setup_description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_artistprofiles_history',
        );
    $objects['audio'] = array(
        'name'=>'Audio',
        'o_name'=>'audio',
        'o_container'=>'audio',
        'sync'=>'yes',
        'table'=>'ciniki_artistprofiles_audio',
        'fields'=>array(
            'artist_id'=>array('name'=>'Artist', 'ref'=>'ciniki.artistprofiles.artist'),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'sequence'=>array('name'=>'Sequence', 'default'=>'1'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'mp3_audio_id'=>array('name'=>'MP3', 'default'=>'0'),
            'wav_audio_id'=>array('name'=>'WAV', 'default'=>'0'),
            'ogg_audio_id'=>array('name'=>'OGG', 'default'=>'0'),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_artistprofiles_history',
        );
    $objects['image'] = array(
        'name'=>'Image',
        'o_name'=>'image',
        'o_container'=>'images',
        'sync'=>'yes',
        'table'=>'ciniki_artistprofiles_images',
        'fields'=>array(
            'artist_id'=>array('name'=>'Artist', 'ref'=>'ciniki.artistprofiles.artist'),
            'name'=>array('name'=>'Name', 'default'=>''),
            'permalink'=>array('name'=>'Permalink'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'image_id'=>array('name'=>'Image', 'default'=>'0', 'ref'=>'ciniki.images.image'),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_artistprofiles_history',
        );
    $objects['link'] = array(
        'name'=>'Link',
        'o_name'=>'link',
        'o_container'=>'links',
        'sync'=>'yes',
        'table'=>'ciniki_artistprofiles_links',
        'fields'=>array(
            'artist_id'=>array('name'=>'Artist', 'ref'=>'ciniki.artistprofiles.artist'),
            'name'=>array('name'=>'Name'),
            'link_type'=>array('name'=>'Type', 'default'=>'1000'),
            'url'=>array('name'=>'URL'),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_artistprofiles_history',
        );
    $objects['tag'] = array(
        'name'=>'Tag',
        'o_name'=>'tag',
        'o_container'=>'tags',
        'sync'=>'yes',
        'table'=>'ciniki_artistprofiles_tags',
        'fields'=>array(
            'artist_id'=>array('name'=>'Artist', 'ref'=>'ciniki.artistprofiles.artist'),
            'tag_type'=>array('name'=>'Type'),
            'tag_name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            ),
        'history_table'=>'ciniki_artistprofiles_history',
        );
    $objects['setting'] = array(
        'type'=>'settings',
        'name'=>'Artist Profiles Settings',
        'table'=>'ciniki_artistprofiles_settings',
        'history_table'=>'ciniki_artistprofiles_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
