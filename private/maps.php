<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_artistprofiles_maps($ciniki) {
	$maps = array();
	$maps['artist'] = array(
        'status'=>array(
            '10'=>'Active',
            '50'=>'Inactive',
            ),
        'flags'=>array(
            0x01=>'Featured',
            ),
        );

	return array('stat'=>'ok', 'maps'=>$maps);
}
?>
