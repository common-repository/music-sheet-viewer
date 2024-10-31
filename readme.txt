=== Music Sheet Viewer ===
Contributors: efreja
Donate link: http://www.partitionnumerique.com/donate-to-music-sheet-viewer-wordpress-plugin/
Tags: musicxml, abc, sheet, music, score, mei, MEI, viewer, MusicXML, pae, PAE, javascript
Requires at least: 4.6
Tested up to: 6.2
Stable tag: trunk
Requires PHP: 5.6.32
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

« Music Sheet Viewer » plugin allows you to display and play, one or more music sheet written in MEI, MusicXML, ABC … in a WordPress page.

== Description ==

« Music Sheet Viewer » allows you to display a digital sheet music with Block editor, through a « Music Sheet Viewer » dedicated block or just by entering its code using the shortcode tag [pn_msv].

The supported music sheet formats are MusicXML (incl. compressed MXL files), MEI, ABC or PAE (RISM notation). The score is displayed as if it had been natively supported by the browser.

When writing scores inline, you do not need to escape HTML entities or anything. Just post your code as-is, even if the code is an XML dialect. The plugin will handle the rest.

The score is resized automatically so as to fit to the available content, you can specify the music font in which the score will be rendered and you can get the music code from a file if you don't want to write the code inline.

You can play the score with many instruments and there's even an option to highlight the notes as they are played!

This plugin supports multi-pages scores and when played, the pages are turned automatically.

*With this plugin, never ever upload a music sheet image to your website that is not zoomable and requires a new upload every time a single note must be changed!!!*

At last, it's fully responsive!

For a complete documentation of the plugin, see [this plugin's homepage](http://www.partitionnumerique.com/music-sheet-viewer-wordpress-plugin/)..

= Shortcode =

Just wrap your music sheet code inside [pn_msv] shortcode, such as :

`[pn_msv format="abc"]
X:1
T:La Vie En Rose
M:C|2
L:1/4
K:C
c3B|AGEc|B3A|GECB|A3G|EB,CB|A4|G2z2|]
[/pn_msv]`

(ABC code example), and the corresponding score will be rendered.

You do not need to escape HTML entities or anything, just post your code as-is. The plugin will handle the rest.

= Block =

* Just enter the same code as the above in the block's text field (but shortcode tags obviously)
* Use the Block's properties to go deeper in the rendering options 

Complete details of the different Music Sheet formats supported and possible shortcode/block
 parameters are described at [this plugin's homepage](http://www.partitionnumerique.com/music-sheet-viewer-wordpress-plugin/)..

= Related Links =

* [Documentation](http://www.partitionnumerique.com/music-sheet-viewer-wordpress-plugin/
  "Usage instructions")

== Screenshots ==

1. Example of how to write a score that can be played, and when played, how the notes can be highlighted.
2. Example of how to write [pn_msv/] shortcodes inside a HTML table and how they are rendered.
3. Example of « Music Sheet Viewer » blocks in the Block editor
4. Settings panel

== Installation ==

= Uploading The Plugin =
Extract all files from the ZIP file, the copy the whole music-sheet-viewer directory and its contents under /wp-content/plugins/.
See Also: « Installing Plugins » article on the WP Codex

= Plugin Activation =
Go to the admin area of your WordPress install and click on the « Plugins » menu. Click on « Activate » for the « Music Sheet Viewer » plugin.

== Upgrade Notice ==

To upgrade from a previous version of this plugin, delete the entire folder and files from the previous version of the plugin and then follow the installation instructions below.

== Changelog ==

= 4.1 =
* New transpose option
* Fix for adding jquery which may be missing on WP fresh install 6.0+
* Tested compatibility with WordPress 6.2

= 4.0.2 =
* Fix issue making that scores won't play unless plugin is deactivated then activated (after upgrade).

= 4.0 =
* Dedicated « Music Sheet Viewer » Settings panel for administrators
* Now comes with 72 MIDI instruments to play the scores with 
* Possibility to uncompress MXL files frontside or backside
* Upload of files with extension .musicxml now supported
* Fix for Uncaught TypeError: wp.serverSideRender is undefined in block.js
* Bundled with Verovio 3.9.0 light
* Tested compatibility with WordPress 5.9

= 3.2.1 =
* Fix for MSV block that may not show in block search field

= 3.2 =
* Embedding of Leland font from MuseScore
* Fixed bug for reading remote files on https
* Fixed small bug on Player progress bar drag on devices
* Fixed small bug with auto modes on Chrome 
* Support of any font used with 'player' and 'highlight' modes, thanks to a Verovio fix 
* Bundled with Verovio 3.6.0 light
* Tested compatibility with WordPress 5.8

= 3.1 =
* Dedicated « Music Sheet Viewer » Block for WordPress 5.0 and above
* Player progress bar touch events now supported
* Upload of plugin's supported file formats (.abc, .mei, .pae, .xml, .mxl) in WordPress now managed
* Fix on some PAE scores
* Tested compatibility with WordPress 5.7

= 3.0.3 =
* Fix for last note played again when song played twice
* Fix for last note played staying highlighted

= 3.0.2 =
* Fix for Incorrect pitch on mobile devices
* Tested compatibility with WordPress 5.6

= 3.0.1 =
* Fixed regression on Safari

= 3.0 =
* Multi-pages scores can now be viewed and played, pages are turned automatically when played
* Mini-player CSS can now be overridden
* Bundled with Verovio 2.5.0 light
* Tested compatibility with WordPress 5.5

= 2.4 =
* Remote files can now be read whatever their location (bypass CORS error)
* MXL MusicXML compressed file format now supported
* Bundled with Verovio 2.5.0 light
* Tested compatibility with WordPress 5.4

= 2.3 =
* Fixed intempestive resize that may occur when the score is played
* Rendering speed increased by 20%
* Bundled with Verovio 2.3.3 light

= 2.2 =
* Addition of 'play' options allowing to play the score and have it highlighted when played
* Support of ABC notation
* Embedding of Petaluma font
* Bundled with Verovio 2.1.0 light
* Bundled with an enhancement of the JavaScript MIDI player (https://github.com/rism-ch/midi-player)

= 2.1 =
* Performances improved by 10%
* Addition of a 'roll' font value, that displays a score in all available fonts

= 2.0 =
* Internal rewriting of the plugin with OOP
* Addition of 'id' and 'class' options allowing CSS styling 
* Bundled with Verovio 2.0.2 light
* Got rid of noLayout, deprecated

= 1.1 =
* Remote URLs can also be used with the 'file' parameter

= 1.0.2 =
* Fixed responsive bug for Firefox on devices

= 1.0.1 =
* Plugin activation or execution warnings appearing in WP's DEBUG mode removed

= 1.0 =
* Initial version. Bundled with Verovio 1.1.6