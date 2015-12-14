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
function ciniki_artistprofiles_flags($ciniki, $modules) {
    $flags = array(
        // 0x01
        array('flag'=>array('bit'=>'1', 'name'=>'Dropbox')),
        array('flag'=>array('bit'=>'2', 'name'=>'Images')),
        array('flag'=>array('bit'=>'3', 'name'=>'Audio')),
        array('flag'=>array('bit'=>'4', 'name'=>'Links')),
        // 0x10
        array('flag'=>array('bit'=>'5', 'name'=>'Setup')),  // Photo and description of setup/studio
//      array('flag'=>array('bit'=>'6', 'name'=>'')),
//      array('flag'=>array('bit'=>'7', 'name'=>'')),
//      array('flag'=>array('bit'=>'8', 'name'=>'')),
        );

    return array('stat'=>'ok', 'flags'=>$flags);
}
?>
