//
// The app to add/edit artistprofiles artist images
//
function ciniki_artistprofiles_images() {
    this.imageFlags = {
        '1':{'name':'Visible'},
        };
    this.init = function() {
        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('Edit Image',
            'ciniki_artistprofiles_images', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.artistprofiles.images.edit');
        this.edit.default_data = {};
        this.edit.data = {};
        this.edit.artist_id = 0;
        this.edit.sections = {
            '_image':{'label':'Image', 'type':'imageform', 'fields':{
                'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
                }},
            'info':{'label':'Information', 'type':'simpleform', 'fields':{
                'name':{'label':'Title', 'type':'text'},
                'flags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.imageFlags},
                }},
            '_description':{'label':'Description', 'type':'simpleform', 'fields':{
                'description':{'label':'', 'type':'textarea', 'size':'medium', 'hidelabel':'yes'},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_artistprofiles_images.saveImage();'},
                'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_artistprofiles_images.deleteImage();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.artistprofiles.imageHistory', 'args':{'tnid':M.curTenantID, 
                'artist_image_id':this.artist_image_id, 'field':i}};
        };
        this.edit.addDropImage = function(iid) {
            M.ciniki_artistprofiles_images.edit.setFieldValue('image_id', iid, null, null);
            return true;
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_artistprofiles_images.saveImage();');
        this.edit.addClose('Cancel');
    };

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_artistprofiles_images', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        }

        if( args.add != null && args.add == 'yes' ) {
            this.showEdit(cb, 0, args.artist_id);
        } else if( args.artist_image_id != null && args.artist_image_id > 0 ) {
            this.showEdit(cb, args.artist_image_id);
        }
        return false;
    }

    this.showEdit = function(cb, iid, eid) {
        if( iid != null ) {
            this.edit.artist_image_id = iid;
        }
        if( eid != null ) {
            this.edit.artist_id = eid;
        }
        if( this.edit.artist_image_id > 0 ) {
            this.edit.reset();
            this.edit.sections._buttons.buttons.delete.visible = 'yes';
            var rsp = M.api.getJSONCb('ciniki.artistprofiles.imageGet', 
                {'tnid':M.curTenantID, 'artist_image_id':this.edit.artist_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_artistprofiles_images.edit.data = rsp.image;
                    M.ciniki_artistprofiles_images.edit.refresh();
                    M.ciniki_artistprofiles_images.edit.show(cb);
                });
        } else {
            this.edit.reset();
            this.edit.sections._buttons.buttons.delete.visible = 'no';
            this.edit.data = {'flags':1};
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveImage = function() {
        if( this.edit.artist_image_id > 0 ) {
            var c = this.edit.serializeFormData('no');
            if( c != '' ) {
                var rsp = M.api.postJSONFormData('ciniki.artistprofiles.imageUpdate', 
                    {'tnid':M.curTenantID, 
                    'artist_image_id':this.edit.artist_image_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } else {
                                M.ciniki_artistprofiles_images.edit.close();
                            }
                        });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeFormData('yes');
            var rsp = M.api.postJSONFormData('ciniki.artistprofiles.imageAdd', 
                {'tnid':M.curTenantID, 'artist_id':this.edit.artist_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_artistprofiles_images.edit.close();
                        }
                    });
        }
    };

    this.deleteImage = function() {
        M.confirm('Are you sure you want to delete this image?',null,function() {
            var rsp = M.api.getJSONCb('ciniki.artistprofiles.imageDelete', {'tnid':M.curTenantID, 
                'artist_image_id':M.ciniki_artistprofiles_images.edit.artist_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_artistprofiles_images.edit.close();
                });
        });
    };
}
