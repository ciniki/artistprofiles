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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'dropboxDownload');

    //
    // Get the list of tenants that have artistprofiles enables and dropbox flag 
    //
    $strsql = "SELECT tnid "
        . "FROM ciniki_tenant_modules "
        . "WHERE package = 'ciniki' "
        . "AND module = 'artistprofiles' "
        . "AND (flags&0x01) = 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.artistprofiles.1', 'msg'=>'Unable to get list of tenants with artist profiles', 'err'=>$rc['err']));
    }
    if( !isset($rc['rows']) ) {
        return array('stat'=>'ok');
    }
    $tenants = $rc['rows'];
    
    foreach($tenants as $tenant) {
        //
        // Load tenant modules
        //
        $rc = ciniki_tenants_checkModuleAccess($ciniki, $tenant['tnid'], 'ciniki', 'artistprofiles');
        if( $rc['stat'] != 'ok' ) { 
            ciniki_cron_logMsg($ciniki, $tenant['tnid'], array('code'=>'ciniki.artistprofiles.37', 'msg'=>'ciniki.artistprofiles not configured', 
                'severity'=>30, 'err'=>$rc['err']));
            continue;
        }

        ciniki_cron_logMsg($ciniki, $tenant['tnid'], array('code'=>'0', 'msg'=>'Updating artistprofiles from dropbox', 'severity'=>'10'));

        //
        // Update the tenant artistprofiles from dropbox
        //
        $rc = ciniki_artistprofiles_dropboxDownload($ciniki, $tenant['tnid']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_cron_logMsg($ciniki, $tenant['tnid'], array('code'=>'ciniki.artistprofiles.38', 'msg'=>'Unable to update artistprofiles', 
                'severity'=>50, 'err'=>$rc['err']));
            continue;
        }
    }

    return array('stat'=>'ok');
}
?>
