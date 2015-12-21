<?php
//
// Description
// ===========
//
// Arguments
// =========
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_artistprofiles_cron_jobs(&$ciniki) {
	ciniki_cron_logMsg($ciniki, 0, array('code'=>'0', 'msg'=>'Checking for artistprofiles jobs', 'severity'=>'5'));

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'dropboxDownload');

	//
	// Get the list of businesses that have artistprofiles enables and dropbox flag 
	//
	$strsql = "SELECT business_id "
		. "FROM ciniki_business_modules "
		. "WHERE package = 'ciniki' "
		. "AND module = 'artistprofiles' "
		. "AND (flags&0x01) = 1 "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2880', 'msg'=>'Unable to get list of businesses with artist profiles', 'err'=>$rc['err']));
	}
	if( !isset($rc['rows']) ) {
		return array('stat'=>'ok');
	}
	$businesses = $rc['rows'];
	
	foreach($businesses as $business) {
		//
		// Load business modules
		//
		$rc = ciniki_businesses_checkModuleAccess($ciniki, $business['business_id'], 'ciniki', 'artistprofiles');
		if( $rc['stat'] != 'ok' ) {	
			ciniki_cron_logMsg($ciniki, $business['business_id'], array('code'=>'2879', 'msg'=>'ciniki.artistprofiles not configured', 
				'severity'=>30, 'err'=>$rc['err']));
			continue;
		}

		ciniki_cron_logMsg($ciniki, $business['business_id'], array('code'=>'0', 'msg'=>'Updating artistprofiles from dropbox', 'severity'=>'10'));

		//
		// Update the business artistprofiles from dropbox
		//
		$rc = ciniki_artistprofiles_dropboxDownload($ciniki, $business['business_id']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_cron_logMsg($ciniki, $business['business_id'], array('code'=>'2878', 'msg'=>'Unable to update artistprofiles', 
				'severity'=>50, 'err'=>$rc['err']));
			continue;
		}
	}

	return array('stat'=>'ok');
}
?>
