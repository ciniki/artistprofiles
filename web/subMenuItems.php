<?php
//
// Description
// -----------
// This function will return the sub menu items for the dropdown menus.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get events for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_artistprofiles_web_subMenuItems(&$ciniki, $settings, $tnid, $args) {
    
    if( !isset($ciniki['tenant']['modules']['ciniki.artistprofiles']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.artistprofiles.36', 'msg'=>"I'm sorry, the file you requested does not exist."));
    }

    //
    // Return nothing if the page format doesn't have a submenu
    //
    if( isset($settings['page-artistprofiles-submenu']) && $settings['page-artistprofiles-submenu'] != 'yes' ) {
        return array('stat'=>'ok', 'submenu'=>array());
    }

    $submenu = array();

    //
    // Check if Categories is enabled
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.artistprofiles', 0x0100) ) {
        //
        // Load the settings for categories
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
        $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_artistprofiles_settings', 'tnid', $tnid, 'ciniki.artistprofiles', 'settings', 'tag-category');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['settings']) ) {
            $psettings = $rc['settings'];
        } else {
            $psettings = array();
        }
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.artistprofiles', 0x0200) ) {
            $submenu['featured'] = array(
                'sequence'=>1, 
                'title'=>'Featured', 
                'sequence'=>((isset($psettings['tag-category-sequence-featured']) && $psettings['tag-category-sequence-featured']!='') 
                    ? $psettings['tag-category-sequence-featured'] : 1),
                'title'=>((isset($psettings['tag-category-title-featured']) && $psettings['tag-category-title-featured']!='')
                    ? $psettings['tag-category-title-featured'] : 'Featured'),
                'image_id'=>((isset($args['content']) && $args['content'] == 'yes' && isset($psettings['tag-category-image-featured']) ) 
                    ? $psettings['tag-category-image-featured'] : ''),
                'content'=>((isset($args['content']) && $args['content'] == 'yes' && isset($psettings['tag-category-content-featured']) ) 
                    ? $psettings['tag-category-content-featured'] : ''),
                'permalink'=>'featured',
                );
        }
        //
        // Load the list of tags
        //
        $strsql = "SELECT DISTINCT tag_name, permalink "
            . "FROM ciniki_artistprofiles_tags "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND tag_type = 10 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artistprofiles', 'category');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) ) {
            $categories = $rc['rows'];
            foreach($categories as $cat) {
                $submenu[$cat['permalink']] = array(
                    'sequence'=>((isset($psettings['tag-category-sequence-' . $cat['permalink']]) && $psettings['tag-category-sequence-' . $cat['permalink']]!='') 
                        ? $psettings['tag-category-sequence-'.$cat['permalink']] : 10),
                    'title'=>((isset($psettings['tag-category-title-' . $cat['permalink']]) && $psettings['tag-category-title-' . $cat['permalink']]!='')
                        ? $psettings['tag-category-title-' . $cat['permalink']] : $cat['tag_name']),
                    'image_id'=>((isset($args['content']) && $args['content'] == 'yes' && isset($psettings['tag-category-image-' . $cat['permalink']]) ) 
                        ? $psettings['tag-category-image-' . $cat['permalink']] : ''),
                    'content'=>((isset($args['content']) && $args['content'] == 'yes' && isset($psettings['tag-category-content-' . $cat['permalink']]) ) 
                        ? $psettings['tag-category-content-' . $cat['permalink']] : ''),
                    'permalink'=>$cat['permalink'],
                    );
            }
        }

        //
        // Sort the submenu
        //
        uasort($submenu, function($a, $b) {
            if( $a['sequence'] == $b['sequence'] ) {
                return strcmp($b['title'], $a['title']);
            }
            return $a['sequence'] < $b['sequence'] ? -1 : 1;
        });
    }

    return array('stat'=>'ok', 'submenu'=>$submenu);
}
?>
