/**
 * Plugin Name:    Music Sheet Viewer
 * Plugin URI:     http://www.partitionnumerique.com/music-sheet-viewer-wordpress-plugin/
 * Description:    Allows you to display music sheet from its MusicXML, MEI, ABC, PAE.. code
 * Author:         Etienne Frejaville
 * Author URI:     http://www.partitionnumerique.com
 * License:        GPL3
 * License URI:    https://www.gnu.org/licenses/gpl-3.0.html
 
 Copyright Etienne Frejaville 2018, 2019, 2020, 2021
 
 Music Sheet Viewer is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 any later version.
 
 Music Sheet Viewer is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.
 
 You should have received a copy of the GNU General Public License
 along with Music Sheet Viewer. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
 */
var _pn_msv_roll;
var curMsv;

class Msv {
    //Not Safari compliant
    //static vrvToolkit = new verovio.toolkit();
    
    constructor(data, iscompressed, options, uniqueid) {
        this.page = 1;
        curMsv = this;

        this.availableFonts = ["Leipzig"
                               ,"Bravura"
                               ,"Gootville"
                               ,"Petaluma"
                               ,"Leland"
                               ];
        this.fontNumber = 0;
        this.rollInterval = 2000;
        this.currentFont = this.availableFonts[this.fontNumber];
        this.elapsed = 0;
        this.ecoExpiration = 60000;
        
        this.data = data;
        this.options = options;
        this.uniqueid = uniqueid;
        
        if (this.options.font != 'roll')
        {
            this.load(iscompressed);
        
            if (this.pageCount > 1)
                this.addNavHandlers();
        }
    }
    
    load(iscompressed) {
        curMsv = this;
        try {
            Msv.vrvToolkit.setOptions(this.options);
            if (iscompressed)
                Msv.vrvToolkit.loadZipDataBuffer(this.data);
            else Msv.vrvToolkit.loadData(this.data);
            this.pageCount = Msv.vrvToolkit.getPageCount();
            
            if (this.pageCount > 0)
                this.loadPage();
        }
        catch(err) {
            var log = Msv.vrvToolkit.getLog();
            console.log("Verovio err="+err+",log="+log );
        }
    }

    loadPage() {
        var svg = Msv.vrvToolkit.renderToSVG(this.page, {});
        jQuery("#"+this.uniqueid).html(svg);
    }

    rollPage() {
        this.fontNumber = 0;
        this.elapsed = 0;
        
        this.loadRolledPage();
        // Workaround as 'this' can't be used in the callback
        var self = this;
        _pn_msv_roll = setInterval(function () {self.loadRolledPage();}, this.rollInterval);
    }
    
    loadRolledPage()
    {
        curMsv = this;
        this.fontNumber = (this.fontNumber+1) % this.availableFonts.length;
        this.currentFont = this.availableFonts[this.fontNumber];

        Msv.vrvToolkit.setOptions({
                                  font: this.currentFont,
                                  scale:this.options.scale,
                                  svgViewBox: true,
                                  breaks:this.options.breaks,
                                  adjustPageHeight: 1
                                  }
                                  );
        Msv.vrvToolkit.loadData(this.data);
        this.pageCount = Msv.vrvToolkit.getPageCount();
        var svg = Msv.vrvToolkit.renderToSVG(this.page, {});
        jQuery("#"+this.uniqueid).html(svg);

        var h = jQuery("#"+this.uniqueid).height();
        var svgAndFont = svg+"<div align='center'>"+this.currentFont+"</div>";
        jQuery("#"+this.uniqueid).height(h);
        jQuery("#"+this.uniqueid).html(svgAndFont);
        
        this.elapsed += this.rollInterval;
        if (this.elapsed == this.ecoExpiration)
            clearInterval(_pn_msv_roll);
    }
    
    nextPage() {
        if (this.page < this.pageCount) {
            this.page++;
            
            if (this == curMsv)
                this.loadPage();
            else
                this.load();
        }
    }
    
    prevPage() {
        if (this.page > 1) {
            this.page--;
            
            if (this == curMsv)
                this.loadPage();
            else
                this.load();
        }
    }
    
    firstPage() {
        this.page = 1;
        
        if (this == curMsv)
            this.loadPage();
        else
            this.load();
    }
    
    lastPage() {
        this.page = this.pageCount;
        
        if (this == curMsv)
            this.loadPage();
        else
            this.load();
    }
    
    getPage() {
        return this.page;
    }
    
    setPage(page) {
        this.page = page;
    }
    
    addNavHandlers() {
        var self = this;
        
        function getOrigin(elt, e)
        {
            var origin = null;
            
            var curOffset = elt.offset();
            var curWidth = elt.width();

            var relX = e.pageX - curOffset.left;
            var relY = e.pageY - curOffset.top;
            if (relX < curWidth/3)
                origin = 'left';
            else if (relX > 2*curWidth/3)
                origin = 'right';
            
            return origin;
        }
        
        jQuery("#"+this.uniqueid).click(function(e){
                                        //console.log("Handler for .click() called for "+self.uniqueid+" page "+self.page );
                                        var origin = getOrigin(jQuery(this), e);

                                        if (origin) {
                                            self.status = 1;
                                            self.timer = setTimeout(function() {
                                                                if (self.status == 1) {
                                                                    if (origin == 'left') self.prevPage();
                                                                    else self.nextPage();
                                                                }
                                                        }, 300);
                                        }
                                        });
   
        jQuery("#"+this.uniqueid).dblclick(function(e){
                                        //console.log("Handler for .dblclick() called for "+self.uniqueid+" page "+self.page );
                                        var origin = getOrigin(jQuery(this), e);

                                        if (origin) {
                                           clearTimeout(self.timer);
                                           self.status = 0;
                                           if (origin == 'left') self.firstPage();
                                           else self.lastPage();
                                        }
                                        });
    }
}

//Safari compliant static initialization
Msv.vrvToolkit = new verovio.toolkit();
