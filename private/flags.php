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
        // 0x0100
        array('flag'=>array('bit'=>'9', 'name'=>'Categories')),
        array('flag'=>array('bit'=>'10', 'name'=>'Featured')),
//      array('flag'=>array('bit'=>'11', 'name'=>'')),
//      array('flag'=>array('bit'=>'12', 'name'=>'')),
        // 0x1000
//      array('flag'=>array('bit'=>'13', 'name'=>'')),
//      array('flag'=>array('bit'=>'14', 'name'=>'')),
//      array('flag'=>array('bit'=>'15', 'name'=>'')),
//      array('flag'=>array('bit'=>'16', 'name'=>'')),
        );

    return array('stat'=>'ok', 'flags'=>$flags);
}
?>
