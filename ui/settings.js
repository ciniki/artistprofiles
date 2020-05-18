//
function ciniki_artistprofiles_settings() {
    //
    // Panels
    //
    this.main = null;
    this.add = null;

    this.cb = null;
    this.toggleOptions = {'off':'Off', 'on':'On'};

//  this.themes = {
//      'Black':'Blue Titles on Black',
//      'Default':'Black Titles on White',
//      };

    this.init = function() {
        //
        // The main panel, which lists the options for production
        //
        this.main = new M.panel('Settings',
            'ciniki_artistprofiles_settings', 'main',
            'mc', 'medium', 'sectioned', 'ciniki.artistprofiles.settings.main');
        this.main.sections = {
            'dropbox':{'label':'Dropbox Profiles', 'fields':{
                'dropbox-artistprofiles':{'label':'Directory', 'type':'text'},
            }},
        };

        this.main.fieldValue = function(s, i, d) { 
            return this.data[i];
        };

        //  
        // Callback for the field history
        //  
        this.main.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.artistprofiles.settingsHistory', 'args':{'tnid':M.curTenantID, 'setting':i}};
        };

        this.main.addButton('save', 'Save', 'M.ciniki_artistprofiles_settings.saveSettings();');
        this.main.addClose('Cancel');
    }

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_artistprofiles_settings', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.showMain(cb);
    }

    //
    // Grab the stats for the tenant from the database and present the list of orders.
    //
    this.showMain = function(cb) {
        M.api.getJSONCb('ciniki.artistprofiles.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_artistprofiles_settings.main;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    }

    this.saveSettings = function() {
        var c = this.main.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.artistprofiles.settingsUpdate', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_artistprofiles_settings.main.close();
            });
        } else {
            M.ciniki_artistprofiles_settings.main.close();
        }
    }
}
