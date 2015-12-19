//
// This app will handle the listing, additions and deletions of artistprofiles.  These are associated business.
//
function ciniki_artistprofiles_main() {
	//
	// Panels
	//
    this.artistStatuses = {
        '10':'Active',
        '50':'Inactive',
        };
    this.artistFlags = {
        '1':{'name':'Featured'},
        };
	this.init = function() {
		//
		// artistprofiles panel
		//
		this.menu = new M.panel('Artist Profiles',
			'ciniki_artistprofiles_main', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.artistprofiles.main.menu');
        this.menu.sections = {
			'artists':{'label':'Artist Profiles', 'type':'simplegrid', 'num_cols':2,
				'noData':'No artist profiles',
				'addTxt':'Add Artist',
				'addFn':'M.ciniki_artistprofiles_main.artistEdit(\'M.ciniki_artistprofiles_main.menuShow();\',0);',
				},
			};
		this.menu.sectionData = function(s) { return this.data[s]; }
		this.menu.noData = function(s) { return this.sections[s].noData; }
		this.menu.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.status_text;
            }
		};
		this.menu.rowFn = function(s, i, d) {
			return 'M.ciniki_artistprofiles_main.artistShow(\'M.ciniki_artistprofiles_main.menuShow();\',\'' + d.id + '\');';
		};
		this.menu.addButton('add', 'Add', 'M.ciniki_artistprofiles_main.artistEdit(\'M.ciniki_artistprofiles_main.menuShow();\');');
		this.menu.addClose('Back');

		//
		// The profile panel 
		//
		this.artist = new M.panel('Artist Profile',
			'ciniki_artistprofiles_main', 'artist',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.artistprofiles.main.artist');
		this.artist.data = {};
		this.artist.artist_id = 0;
		this.artist.sections = {
            '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
            }},
			'_caption':{'label':'', 'aside':'yes', 'list':{
				'primary_image_caption':{'label':'Caption', 'type':'text'},
				}},
			'info':{'label':'Service', 'aside':'yes', 'list':{
				'name':{'label':'Name'},
				'status_text':{'label':'Status'},
				'flags_text':{'label':'Options'},
                'categories':{'label':'Categories'},
            }},
//            '_tabs':{'label':'', 'type':'paneltabs', 'selected':'recent', 'tabs':{
//                'bio':{'label':'Overview', 'fn':'M.ciniki_artistprofiles_main.artistShow(null,null,"recent");'},
//                'setup':{'label':'Trades', 'fn':'M.ciniki_artistprofiles_main.artistShow(null,null,"trades");'},
//            }},
            'synopsis':{'label':'Synopsis', 'type':'htmlcontent'},
            'description':{'label':'Bio', 'type':'htmlcontent'},
			'images':{'label':'Gallery', 'type':'simplethumbs'},
			'_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add Image',
				'addFn':'M.startApp(\'ciniki.artistprofiles.images\',null,\'M.ciniki_artistprofiles_main.artistShow();\',\'mc\',{\'artist_id\':M.ciniki_artistprofiles_main.artist.artist_id,\'add\':\'yes\'});',
				},
            'videos':{'label':'Videos', 'type':'simplegrid', 'num_cols':1,
                'cellClasses':['multiline'],
                'addTxt':'Add Video',
				'addFn':'M.startApp(\'ciniki.artistprofiles.links\',null,\'M.ciniki_artistprofiles_main.artistShow();\',\'mc\',{\'artist_id\':M.ciniki_artistprofiles_main.artist.artist_id,\'add\':\'yes\'});',
                },
//            'audio':
            'links':{'label':'Links', 'type':'simplegrid', 'num_cols':1,
                'cellClasses':['multiline'],
                'addTxt':'Add Link',
				'addFn':'M.startApp(\'ciniki.artistprofiles.links\',null,\'M.ciniki_artistprofiles_main.artistShow();\',\'mc\',{\'artist_id\':M.ciniki_artistprofiles_main.artist.artist_id,\'add\':\'yes\'});',
                },
            '_buttons':{'label':'', 'buttons':{
                
                }},
		};
		this.artist.sectionData = function(s) {
            if( s == 'info' || s == '_caption' ) { return this.sections[s].list; }
			return this.data[s];
		};
        this.artist.noData = function(s) {
            if( this.sections[s].noData != null ) { return this.sections[s].noData; }
            return null;
        }
        this.artist.listLabel = function(s, i, d) {
            return d.label;
        };
		this.artist.listValue = function(s, i, d) {
            return this.data[i];
		};
        this.artist.fieldValue = function(s, i, d) {
            return this.data[i];
        }
        this.artist.cellValue = function(s, i, j, d) {
            if( s == 'videos' || s == 'links' ) {
                return '<span class="maintext">' + d.name + '</span><span class="subtext">' + d.url + '</span>';
            }
        };
        this.artist.rowFn = function(s, i, d) {
            if( s == 'videos' || s == 'links' ) {
                return 'M.startApp(\'ciniki.artistprofiles.links\',null,\'M.ciniki_artistprofiles_main.artistShow();\',\'mc\',{\'link_id\':\'' + d.id + '\'});';
            }
            return '';
        };
		this.artist.thumbFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.artistprofiles.images\',null,\'M.ciniki_artistprofiles_main.artistShow();\',\'mc\',{\'artist_image_id\':\'' + d.id + '\'});';
		};
        this.artist.addButton('edit', 'Edit', 'M.ciniki_artistprofiles_main.artistEdit(\'M.ciniki_artistprofiles_main.artistShow();\',M.ciniki_artistprofiles_main.artist.artist_id);');
		this.artist.addClose('Back');

		//
		// The panel for editing an artist
		//
		this.edit = new M.panel('Artist Profile',
			'ciniki_artistprofiles_main', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.artistprofiles.main.edit');
		this.edit.data = null;
		this.edit.artist_id = 0;
        this.edit.sections = { 
			'_image':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
				}},
			'_caption':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_caption':{'label':'Caption', 'type':'text'},
				}},
            'general':{'label':'Service', 'aside':'yes', 'fields':{
                'name':{'label':'Name', 'type':'text'},
                'status':{'label':'Status', 'type':'toggle', 'toggles':this.artistStatuses},
                'flags':{'label':'Options', 'type':'flags', 'join':'yes', 'flags':this.artistFlags},
                }}, 
            '_categories':{'label':'Categories', 'aside':'yes', 'fields':{
                'categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category: '},
                }},
			'_synopsis':{'label':'Synopsis', 'fields':{
                'synopsis':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
                }},
			'_description':{'label':'Description', 'fields':{
                'description':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
                }},
			'_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_artistprofiles_main.artistSave();'},
                'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_artistprofiles_main.artistDelete();'},
                }},
            };  
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.artistprofiles.artistHistory', 'args':{'business_id':M.curBusinessID, 
				'artist_id':this.artist_id, 'field':i}};
		}
		this.edit.addDropImage = function(iid) {
			M.ciniki_artistprofiles_main.edit.setFieldValue('primary_image_id', iid, null, null);
			return true;
		};
		this.edit.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_artistprofiles_main.artistSave();');
		this.edit.addClose('Cancel');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_artistprofiles_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

        this.menuShow(cb);
	}

	this.menuShow = function(cb) {
		this.menu.data = {};
        M.api.getJSONCb('ciniki.artistprofiles.artistList', {'business_id':M.curBusinessID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_artistprofiles_main.menu;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
	};

	this.artistShow = function(cb, sid) {
		if( sid != null ) { this.artist.artist_id = sid; }
        var args = {'business_id':M.curBusinessID, 'artist_id':this.artist.artist_id, 'images':'yes', 'audio':'yes', 'links':'yes', 'videos':'yes'};
		M.api.getJSONCb('ciniki.artistprofiles.artistGet', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_artistprofiles_main.artist;
            p.data = rsp.artist;
            p.refresh();
            p.show(cb);
        });
	};

	this.artistEdit = function(cb, aid) {
		this.edit.reset();
		if( aid != null ) { this.edit.artist_id = aid; }
		this.edit.sections._buttons.buttons.delete.visible = (this.edit.artist_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.artistprofiles.artistGet', {'business_id':M.curBusinessID, 'artist_id':this.edit.artist_id, 'categories':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_artistprofiles_main.edit;
            p.data = rsp.artist;
            p.sections._categories.fields.categories.tags = [];
            if( rsp.categories != null ) {
                for(i in rsp.categories) {
                    p.sections._categories.fields.categories.tags.push(rsp.categories[i].tag.name);
                }
            }
            p.refresh();
            p.show(cb);
        });
	};

	this.artistSave = function() {
		if( this.edit.artist_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.artistprofiles.artistUpdate', {'business_id':M.curBusinessID, 'artist_id':M.ciniki_artistprofiles_main.edit.artist_id}, c,
					function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					M.ciniki_artistprofiles_main.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
            M.api.postJSONCb('ciniki.artistprofiles.artistAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                if( rsp.id > 0 ) {
                    var cb = M.ciniki_artistprofiles_main.edit.cb;
                    M.ciniki_artistprofiles_main.edit.close();
                    M.ciniki_artistprofiles_main.artistShow(cb,rsp.id);
                } else {
                    M.ciniki_artistprofiles_main.edit.close();
                }
            });
		}
	};

	this.artistDelete = function() {
		if( confirm("Are you sure you want to remove '" + this.edit.data.name + "'?") ) {
			M.api.getJSONCb('ciniki.artistprofiles.artistDelete', 
				{'business_id':M.curBusinessID, 'artist_id':M.ciniki_artistprofiles_main.edit.artist_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_artistprofiles_main.edit.close();
				});
		}
	};

};
