<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get artistprofiles for.
//
// Returns
// -------
//
function ciniki_artistprofiles_hooks_uiSettings($ciniki, $tnid, $args) {

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array(), 'settings_menu_items'=>array());

    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['tenant']['modules']['ciniki.artistprofiles'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>3700,
            'label'=>'Artist Profiles', 
            'edit'=>array('app'=>'ciniki.artistprofiles.main'),
            'add'=>array('app'=>'ciniki.artistprofiles.main', 'args'=>array('artist_id'=>0)),
            'search'=>array(
                'method'=>'ciniki.artistprofiles.artistSearch',
                'args'=>array(),
                'container'=>'artists',
                'cols'=>1,
                'cellValues'=>array(
                    '0'=>'d.name;',
                    ),
                'noData'=>'No artists found',
                'edit'=>array('method'=>'ciniki.artistprofiles.main', 'args'=>array('artist_id'=>'d.id;')),
                ),
            );
        $rsp['menu_items'][] = $menu_item;

    } 

    //
    // Check if Dropbox has been enabled
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.artistprofiles', 0x01) 
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $rsp['settings_menu_items'][] = array('priority'=>3700, 'label'=>'Artist Profiles', 'edit'=>array('app'=>'ciniki.artistprofiles.settings'));
    }

    return $rsp;
}
?>
