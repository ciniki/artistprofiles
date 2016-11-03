<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get options for.
//
// args:            The possible arguments for profiles
//
//
// Returns
// -------
//
function ciniki_artistprofiles_hooks_webOptions(&$ciniki, $business_id, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['business']['modules']['ciniki.artistprofiles']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artistprofiles.6', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Get the settings from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'business_id', $business_id, 'ciniki.web', 'settings', 'page-artistprofiles');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        $settings = array();
    } else {
        $settings = $rc['settings'];
    }

    $pages['ciniki.artistprofiles'] = array('name'=>'Artist Profiles', 'options'=>array(
        array('label'=>'Sidebar',
            'setting'=>'page-artistprofiles-sidebar', 
            'type'=>'toggle',
            'value'=>(isset($settings['page-artistprofiles-sidebar'])?$settings['page-artistprofiles-sidebar']:'no'),
            'toggles'=>array(
                array('value'=>'no', 'label'=>'No'),
                array('value'=>'yes', 'label'=>'Yes'),
                ),
            ),
        ));

    return array('stat'=>'ok', 'pages'=>$pages);
}
?>
