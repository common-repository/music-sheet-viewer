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
var ids = [];

class MsvPlayer {
    
  constructor(playerid, highlight, msv) {
    function loadScore() {
        if (_pn_msv_roll) clearInterval(_pn_msv_roll);
        ids = [];
        var currentPlayerMsv = currentPlayer.getMsv();
        currentPlayerMsv.setPage(1); // restarts always from page 1
        currentPlayerMsv.load();
        currentPlayer.loadSong('data:audio/midi;base64,' + Msv.vrvToolkit.renderToMIDI());
    }
      
    // midiUpdate and midiStop are two callback functions passed to the MIDI player
    // time needs to - 400 for adjustment
    function midiUpdateHighlight(time) {
        // in case we navigated inside another reader
        var currentPlayerMsv = currentPlayer.getMsv();
        if (currentPlayerMsv != curMsv)
        {
            currentPlayer.stop();
            // and we force that the song will be reloaded
            currentPlayer.midiPlayer_songIsLoaded = false;
            return;
        }
        
        // For page turn & notes highlight/switch off
        var vrvTime = Math.max(0, time - 400);
        var elementsattime = Msv.vrvToolkit.getElementsAtTime(vrvTime);
        if (elementsattime.page > 0) {
            if (elementsattime.page != currentPlayer.getMsv().getPage()) {
                currentPlayerMsv.setPage(elementsattime.page);
                currentPlayerMsv.loadPage();
            }
            
            if ((elementsattime.notes.length > 0) && (ids != elementsattime.notes)) {
                ids.forEach(function(noteid) {
                    if (jQuery.inArray(noteid, elementsattime.notes) == -1) {
                        jQuery("#" + noteid).attr("fill", "#000").attr("stroke", "#000");
                    }
                });
                ids = elementsattime.notes;
                ids.forEach(function(noteid) {
                    if (jQuery.inArray(noteid, elementsattime.notes) != -1) {
                        jQuery("#" + noteid).attr("fill", "#c00").attr("stroke", "#c00");
                    }
                });
            }
        }
    }
      
    function midiUpdate(time) {
        // in case we navigated inside another reader
        var currentPlayerMsv = currentPlayer.getMsv();
        if (currentPlayerMsv != curMsv)
        {
            currentPlayer.stop();
            // and we force that the song will be reloaded
            currentPlayer.midiPlayer_songIsLoaded = false;
            return;
        }
        
        // For page turn even without highlight
        var vrvTime = Math.max(0, time - 400);
        var elementsattime = Msv.vrvToolkit.getElementsAtTime(vrvTime);
        if (elementsattime.page > 0) {
            if (elementsattime.page != currentPlayer.getMsv().getPage()) {
                currentPlayerMsv.setPage(elementsattime.page);
                currentPlayerMsv.loadPage();
            }
        }
    }
    
    function midiStop() {
        ids.forEach(function(noteid) {
                    jQuery("#" + noteid ).attr("fill", "#000").attr("stroke", "#000");
        });
    }
    
    if (highlight)
        this.player = new MidiPlayerClass(playerid,{
                                      loadSong: loadScore
                                      ,onUpdate: midiUpdateHighlight
                                      ,onStop: midiStop
                                      }
                                      ,msv);
    else
        this.player = new MidiPlayerClass(playerid,{
                                      loadSong: loadScore
                                      ,onUpdate: midiUpdate
                                      }
                                      ,msv);
    } // end ctor
    
    getPlayer()
    {
        return this.player;
    }
}
