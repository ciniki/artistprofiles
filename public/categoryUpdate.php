<?php
//
// Description
// -----------
// This method will update the details for a category.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_artistprofiles_categoryUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'category'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'),
        'title'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Title'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'),
        'image'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'checkAccess');
    $rc = ciniki_artistprofiles_checkAccess($ciniki, $args['business_id'], 'ciniki.artistprofiles.categoryUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Grab the settings for the business from the database
	//
	$strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_artistprofiles_settings "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.artistprofiles');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// The list of allowed fields for updating
	//
	$changelog_fields = array(
		'title',
		'sequence',
		'image',
		'content',
		);

	//
	// Check each valid setting and see if a new value was passed in the arguments for it.
	// Insert or update the entry in the ciniki_artistprofiles_settings table
	//
	foreach($changelog_fields as $field) {
        if( isset($args[$field]) ) {
            if( isset($settings['tag-category-' . $field . '-' . $args['category']]) ) {
                // Update the settings
                if( $settings['tag-category-' . $field . '-' . $args['category']] != $args[$field] ) {
                    $strsql = "UPDATE ciniki_artistprofiles_settings "
                        . "SET detail_value = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' "
                        . ", last_updated = UTC_TIMESTAMP() "
                        . "WHERE detail_key = 'tag-category-" . ciniki_core_dbQuote($ciniki, $field . "-" . $args['category']) . "' "
                        . "";
                    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.artistprofiles');
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                        return $rc;
                    }
                    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.artistprofiles', 'ciniki_artistprofiles_history', $args['business_id'], 
                        2, 'ciniki_artistprofiles_settings', 'tag-category-' . $field . '-' . $args['category'], 'detail_value', $args[$field]);
                    $ciniki['syncqueue'][] = array('push'=>'ciniki.artistprofiles.setting', 
                        'args'=>array('id'=>'tag-category-' . $field . '-' . $args['category']));
                }
            } else {
                // Add the setting
                $strsql = "INSERT INTO ciniki_artistprofiles_settings (business_id, detail_key, detail_value, date_added, last_updated) "
                    . "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'"
                    . ", 'tag-category-" . ciniki_core_dbQuote($ciniki, $field . '-' . $args['category']) . "' "
                    . ", '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "'"
                    . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) ";
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.artistprofiles');
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.artistprofiles');
                    return $rc;
                }
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.artistprofiles', 'ciniki_artistprofiles_history', $args['business_id'], 
                    1, 'ciniki_artistprofiles_settings', 'tag-category-' . $field . '-' . $args['category'], 'detail_value', $args[$field]);
                $ciniki['syncqueue'][] = array('push'=>'ciniki.artistprofiles.setting', 
                    'args'=>array('id'=>'tag-category-' . $field . '-' . $args['category']));
            }
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.artistprofiles');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'artistprofiles');

	return array('stat'=>'ok');
}
?>
