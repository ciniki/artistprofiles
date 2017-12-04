<?php
//
// Description
// -----------
// This method will turn the artistprofiles settings for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get the ATDO settings for.
// 
// Returns
// -------
//
function ciniki_artistprofiles_categoryGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'category'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'), 
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
    $rc = ciniki_artistprofiles_checkAccess($ciniki, $args['tnid'], 'ciniki.artistprofiles.categoryGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    
    //
    // Grab the settings for the tenant from the database
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_artistprofiles_settings "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND detail_key LIKE 'tag-category-%-" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.artistprofiles', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = array();
    if( isset($rc['settings']) ) {
        $settings = $rc['settings'];
    }

    $category = array(
        'title'=>(isset($settings['tag-category-title-' . $args['category']]) ? $settings['tag-category-title-' . $args['category']] : ''),
        'sequence'=>(isset($settings['tag-category-sequence-' . $args['category']]) ? $settings['tag-category-sequence-' . $args['category']] : ''),
        'image'=>(isset($settings['tag-category-image-' . $args['category']]) ? $settings['tag-category-image-' . $args['category']] : ''),
        'content'=>(isset($settings['tag-category-content-' . $args['category']]) ? $settings['tag-category-content-' . $args['category']] : ''),
        );

    return array('stat'=>'ok', 'category'=>$category);
}
?>
