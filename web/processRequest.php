<?php
//
// Description
// -----------
// This function will process a web request for the blog module.
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure.
// business_id:		The ID of the business to get post for.
//
// args:			The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_artistprofiles_web_processRequest(&$ciniki, $settings, $business_id, $args) {

	if( !isset($ciniki['business']['modules']['ciniki.artistprofiles']) ) {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'3041', 'msg'=>"I'm sorry, the page you requested does not exist."));
	}
	$page = array(
		'title'=>$args['page_title'],
		'breadcrumbs'=>$args['breadcrumbs'],
		'blocks'=>array(),
		);

    //
    // Get the list of categories
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.artistprofiles', 0x0100) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'web', 'subMenuItems');
        $rc = ciniki_artistprofiles_web_subMenuItems($ciniki, $settings, $business_id, array('content'=>'yes'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['submenu']) ) {
            $categories = $rc['submenu'];
        }
    }

	//
	// Setup titles
	//
	if( count($page['breadcrumbs']) == 0 ) {
		$page['breadcrumbs'][] = array('name'=>'Artists', 'url'=>$args['base_url']);
	}

	$display = '';
	$ciniki['response']['head']['og']['url'] = $args['domain_base_url'];

	//
	// Parse the url to determine what was requested
	//
	
	//
	// Setup the base url as the base url for this page. This may be altered below
	// as the uri_split is processed, but we do not want to alter the original passed in.
	//
	$base_url = $args['base_url']; // . "/" . $args['blogtype'];

	//
	// Check if we are to display an image, from the gallery, or latest images
	//
	$display = '';

//    $page['blocks'][] = array('type'=>'content', 'html'=>'<pre>' . print_r($categories, true) . "</pre>");
//	return array('stat'=>'ok', 'page'=>$page);

    $uri_split = $args['uri_split'];
   
    //
    // First check if there is a category and remove from uri_split
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.artistprofiles', 0x0100) 
        && isset($categories) 
        && isset($uri_split[0]) 
        && isset($categories[$uri_split[0]])
        ) {
        $category = $categories[$uri_split[0]];
        $page['title'] = $category['title'];
        $breadcrumbs = array('name'=>$category['title'], 'url'=>$base_url . '/' . $category['permalink']);
        $base_url .= '/' . $category['permalink'];
        array_shift($uri_split);
    }
    
    //
    // Check for an artist
    //
	if( isset($uri_split[0]) && $uri_split[0] != '' ) {
		$artist_permalink = $uri_split[0];
		$display = 'artist';
		//
		// Check for gallery pic request
		//
		if( isset($uri_split[1]) && $uri_split[1] == 'gallery'
			&& isset($uri_split[2]) && $uri_split[2] != '' 
			) {
			$image_permalink = $uri_split[2];
			$display = 'artistpic';
		}
		$ciniki['response']['head']['og']['url'] .= '/' . $artist_permalink;
		$base_url .= '/' . $artist_permalink;
	}

	//
	// A category was found, so display the list of artists in that category
	//
    elseif( isset($category) && count($category) > 0 ) {
        $display = 'categorylist';
    }
	//
	// There is a list of categories, so display the list
	//
    elseif( isset($categories) && count($categories) > 0 ) {
        $display = 'categories';
    }
    //
    // No categories, display the list
    //
	else {
		$display = 'list';
	}

    
    if( $display == 'list' || ($display == 'categorylist' && $category['permalink'] == 'featured') ) {
        //
        // Display list as thumbnails
        //
        $strsql = "SELECT id, name, permalink, primary_image_id AS image_id "
            . "FROM ciniki_artistprofiles "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND status = 10 "
            . "";
        if( $category['permalink'] == 'featured' ) {
            $strsql .= "AND (flags&0x01) = 0x01 ";
        }
        $strsql .= "ORDER BY sort_name ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artistprofiles', 'artist');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
            $page['blocks'][] = array('type'=>'content', 'content'=>"There are currently no artist profiles available. Please check back soon.");
        } else {
            $page['blocks'][] = array('type'=>'tradingcards', 'base_url'=>$base_url, 'cards'=>$rc['rows']);
        }
    }

    elseif( $display == 'categorylist' ) {
        //
        // Display list as thumbnails
        //
        $strsql = "SELECT ciniki_artistprofiles.id, "
            . "ciniki_artistprofiles.name, "
            . "ciniki_artistprofiles.subname, "
            . "ciniki_artistprofiles.permalink, "
            . "ciniki_artistprofiles.primary_image_id AS image_id "
            . "FROM ciniki_artistprofiles_tags, ciniki_artistprofiles "
            . "WHERE ciniki_artistprofiles_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_artistprofiles_tags.tag_type = 10 "
            . "AND ciniki_artistprofiles_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $category['permalink']) . "' "
            . "AND ciniki_artistprofiles_tags.artist_id = ciniki_artistprofiles.id "
            . "AND ciniki_artistprofiles.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_artistprofiles.status = 10 "
            . "ORDER BY sort_name "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artistprofiles', 'artist');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
            $page['blocks'][] = array('type'=>'content', 'content'=>"There are currently no artist profiles available. Please check back soon.");
        } else {
            $page['blocks'][] = array('type'=>'tradingcards', 'base_url'=>$base_url, 'cards'=>$rc['rows']);
        }

    }

    elseif( $display == 'categories' ) {
        $page['blocks'][] = array('type'=>'tagimages', 'base_url'=>$base_url, 'tags'=>$categories);
    }

	elseif( $display == 'artist' || $display == 'artistpic' ) {
        if( isset($category) ) {
            $ciniki['response']['head']['links'][] = array('rel'=>'canonical', 'href'=>$args['base_url'] . '/' . $artist_permalink);
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'artistprofiles', 'private', 'artistLoad');
        $rc = ciniki_artistprofiles_artistLoad($ciniki, $business_id, $artist_permalink, array('images'=>'yes', 'audio'=>'yes', 'videos'=>'yes', 'links'=>'yes'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['artist']) && $rc['artist']['status'] != 10 ) {
            return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'3038', 'msg'=>"We're sorry, the page you requested is not available."));
        }
        if( !isset($rc['artist']) ) {
            return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'3043', 'msg'=>"We're sorry, the page you requested is not available."));
        } else {
            $artist = $rc['artist'];
            $page['title'] = $artist['name'];
            if( isset($artist['subname']) && $artist['subname'] != '' ) {
                $page['subtitle'] = $artist['subname'];
            }
            $breadcrumbs = array('name'=>$artist['name'], 'url'=>$base_url . '/' . $artist['permalink']);
            $base_url .= '/' . $artist['permalink'];
            if( isset($artist['primary_image_id']) && $artist['primary_image_id'] > 0 ) {
                $page['blocks'][] = array('type'=>'image', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$artist['primary_image_id'], 
                    'title'=>$artist['name'], 'caption'=>$artist['primary_image_caption']);
            }
            if( isset($artist['description']) && $artist['description'] != '' ) {
                $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$artist['description']);
            } elseif( isset($artist['synopsis']) && $artist['synopsis'] != '' ) {
                $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$artist['synopsis']);
            }
			if( isset($artist['links']) && count($artist['links']) > 0 ) {
				$page['blocks'][] = array('type'=>'links', 'section'=>'links', 'title'=>'Links', 'links'=>$artist['links']);
			}
			if( isset($artist['videos']) && count($artist['videos']) > 0 ) {
				$page['blocks'][] = array('type'=>'videolinks', 'section'=>'videos', 'title'=>'Videos', 'videos'=>$artist['videos']);
			}
            // Add share buttons  
            if( !isset($settings['page-artistprofiles-share-buttons']) || $settings['page-artistprofiles-share-buttons'] == 'yes' ) {
                $page['blocks'][] = array('type'=>'sharebuttons', 'section'=>'share', 'pagetitle'=>$artist['name'], 'tags'=>array());
            }
        }
    }

	//
	// Return error if nothing found to display
	//
	else {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'3044', 'msg'=>"We're sorry, the page you requested is not available."));
	}

	//
	// Setup the sidebar
	//
	if( isset($settings['page-artistprofiles-sidebar']) && $settings['page-artistprofiles-sidebar'] == 'yes' && isset($category) ) { 
		$page['sidebar'] = array();	

        //
        // Get the list of artists for the current category
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.artistprofiles', 0x0200) && $category['permalink'] == 'featured' ) {
            $strsql = "SELECT ciniki_artistprofiles.id, "
                . "ciniki_artistprofiles.name, "
                . "ciniki_artistprofiles.subname, "
                . "ciniki_artistprofiles.permalink "
                . "FROM ciniki_artistprofiles "
                . "WHERE ciniki_artistprofiles.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND (flags&0x01) = 0x01 "
                . "AND status = 10 "
                . "ORDER BY sort_name "
                . "";
        } else {
            $strsql = "SELECT ciniki_artistprofiles.id, "
                . "ciniki_artistprofiles.name, "
                . "ciniki_artistprofiles.subname, "
                . "ciniki_artistprofiles.permalink "
                . "FROM ciniki_artistprofiles_tags, ciniki_artistprofiles "
                . "WHERE ciniki_artistprofiles_tags.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_artistprofiles_tags.tag_type = 10 "
                . "AND ciniki_artistprofiles_tags.permalink = '" . ciniki_core_dbQuote($ciniki, $category['permalink']) . "' "
                . "AND ciniki_artistprofiles_tags.artist_id = ciniki_artistprofiles.id "
                . "AND ciniki_artistprofiles.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND status = 10 "
                . "ORDER BY sort_name "
                . "";
        }
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.artistprofiles', 'profile');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
        if( isset($rc['rows']) ) {
            $artists = $rc['rows'];
            //
            // FIXME: Finish code for sidebar menu
            //
        }
	}

	return array('stat'=>'ok', 'page'=>$page);
}
?>
