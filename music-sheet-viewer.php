<?php
/**
 * Plugin Name:    Music Sheet Viewer
 * Plugin URI:     http://www.partitionnumerique.com/music-sheet-viewer-wordpress-plugin/
 * Description:    Allows you to display sheet music from its MusicXML, MEI, ABC, PAE.. code
 * Author:         Etienne Frejaville
 * Author URI:     http://www.partitionnumerique.com
 * License:        GPL3
 * License URI:    https://www.gnu.org/licenses/gpl-3.0.html
 * Version:        4.1

Copyright Etienne Frejaville 2018, 2019, 2020, 2021, 2022, 2023

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

// Options & default values for Settings
const MSV_MXL_UNZIP_METH = 'msv_mxl_unzip_method';
const MSV_MXL_UNZIP_METH_DFLT = 'back';

const MSV_INSTR = 'msv_instrument';
const MSV_INSTR_DFLT = '000_acoustic_grand_piano';
    
if(!class_exists('MusicSheetViewerPlugin'))
{
	class MusicSheetViewerPlugin
	{
        const MSV_VERSION = '4.1';
        const VRV_VERSION = '3.9.0';
        
        const MSV_SHORTCODE = 'pn_msv';
        const MSV_BLOCK = 'music-sheet-viewer/pn-msv';
        const MSV_MAX_CONTENT_LENGTH = 4096;
        const MSV_DEBUGJS = 0;
        
        // Options
        const MSV_OPTION_FORMAT = 'format';
        const MSV_OPTION_FONT = 'font';
        const MSV_OPTION_LAYOUT = 'layout';
        const MSV_OPTION_FILE = 'file';
        const MSV_OPTION_ID = 'id';
        const MSV_OPTION_CLASS = 'class';
        const MSV_OPTION_PLAY = 'play';
        const MSV_OPTION_TRANSP = 'transpose';
        
        // Fonts
        const MSV_FONT_ROLL = 'roll';
        const MSV_FONT_LEIPZIG = 'Leipzig';
        const MSV_FONT_BRAVURA = 'Bravura';
        const MSV_FONT_GOOTVILLE = 'Gootville';
        const MSV_FONT_PETALUMA = 'Petaluma';
        const MSV_FONT_LELAND = 'Leland';
        
        // Keep roll in 1st position, add new fonts at the end if any
        // WARNING : Also in Msv.js and in block.js
        const MSV_FONTS = array(self::MSV_FONT_ROLL
                                , self::MSV_FONT_LEIPZIG
                                , self::MSV_FONT_BRAVURA
                                , self::MSV_FONT_GOOTVILLE
                                , self::MSV_FONT_PETALUMA
                                , self::MSV_FONT_LELAND
                                );
        
        // Formats (useful for inline mode only)
        const MSV_FORMAT_PAE = 'pae';
        const MSV_FORMAT_MEI = 'mei';
        const MSV_FORMAT_XML = 'xml';
        const MSV_FORMAT_ABC = 'abc';
        
        const MSV_FORMATS = array(self::MSV_FORMAT_PAE
                                  , self::MSV_FORMAT_MEI
                                  , self::MSV_FORMAT_XML
                                  , self::MSV_FORMAT_ABC
                                  );
        
        // Layouts
        const MSV_LAYOUT_JUSTIFIED = 'justified';
        const MSV_LAYOUTS = array(self::MSV_LAYOUT_JUSTIFIED);
        
        // Play
        const MSV_PLAY_PLAYER = 'player';
        const MSV_PLAY_AUTO = 'auto';
        const MSV_PLAY_HIGHLIGHT = 'highlight';
        const MSV_PLAY_AUTOHIGH = 'autohigh';
        
        const MSV_PLAYS = array(self::MSV_PLAY_PLAYER
                                ,self::MSV_PLAY_AUTO
                                ,self::MSV_PLAY_HIGHLIGHT
                                ,self::MSV_PLAY_AUTOHIGH
                                );
        
        // selected options
        protected $myfont;
        protected $myformat;
        protected $mylayout;
        protected $myfile;
        protected $myid;
        protected $myclass;
        protected $myplay;
        protected $mytransp;
        
        protected $uniqueid;
        
		/**
		 * Constructs the plugin object
		 */
		public function __construct()
		{
            if (get_option(MSV_INSTR) === false)
            { // Workaround as there is no plugin activation at upgrade time
                add_option(MSV_INSTR, MSV_INSTR_DFLT);
                add_option(MSV_MXL_UNZIP_METH, MSV_MXL_UNZIP_METH_DFLT);
            }
            
            // Register activation/deactivation hooks prior to any other thing otherwise they are not processed correctly
            register_activation_hook(__FILE__, array($this, 'activate'));
            register_deactivation_hook(__FILE__, array($this, 'deactivate'));
            register_uninstall_hook( __FILE__, 'msv_uninstall' );
            add_action('init', array($this, 'add_hooks'));
		}
        
        /**
         * Allows that file extensions supported by the plugin are enabled to uploading in WP's media
         */
        public function allowed_mimetypes($mime_types)
        {
            $mime_types['abc'] = 'text/plain';
            $mime_types['pae'] = 'text/plain';
            $mime_types['mei'] = 'text/xml';
            $mime_types['mxl'] = 'application/vnd.recordare.musicxml';
            $mime_types['musicxml'] = 'text/xml';
            $mime_types['xml'] = 'text/xml';
            
            return $mime_types;
        }
        
        /**
         * Adds hooks & shortcode
         */
        public function add_hooks()
        {
            add_action('wp_enqueue_scripts', array($this,'hook_javascript'));
            // enqueue block assets on the front-end and the editor
            add_action('enqueue_block_assets', array($this,'register_block'));
            add_shortcode (self::MSV_SHORTCODE, array($this, 'process_shortcode'));
            add_filter('upload_mimes', array($this, 'allowed_mimetypes'), 1, 1);

            // Ajax hooks
            add_action('wp_ajax_readfile', array($this, 'read_score_file'));
            add_action('wp_ajax_nopriv_readfile', array($this, 'read_score_file'));
        }
        
        /**
         * Register MSV Block
         */
        public function register_block()
        {
            // Skip block registration if Gutenberg is not enabled/merged.
            if (!function_exists('register_block_type')) {
                error_log('Music Sheet Viewer ERROR : Cannot register block : Function register_block_type does not exist');
                return;
            }
            
            wp_enqueue_script('music-sheet-viewer-pn-msv',
                               plugins_url('js/block.js', __FILE__),
                               array(
                                     'wp-blocks'
                                     ,'wp-i18n'
                                     ,'wp-element'
                                     ,'wp-components'
                                     ,'wp-block-editor'
                                   //  ,'wp-server-side-render'
                                     ),
                               //filemtime(dirname(__FILE__)."/js/block.js")
                                self::MSV_VERSION
                               );
            // !!!! _ forbidden in extension/bloc
            // https://github.com/WordPress/gutenberg/issues/13074
            if (false == register_block_type(self::MSV_BLOCK, array(
                                                                   'editor_script' => 'music-sheet-viewer-pn-msv',
                                                                   'render_callback' => array($this, 'process_block'),
                                                                   'attributes' => [
                                                                        'content' => [
                                                                            'default' => NULL,
                                                                            'type' => 'string'
                                                                        ],
                                                                        'format' => [
                                                                            'default' => 'pae',
                                                                            'type' => 'string'
                                                                        ],
                                                                        'file' => [
                                                                            'default' => NULL,
                                                                            'type' => 'string'
                                                                        ],
                                                                        'font' => [
                                                                            'default' => 'Leipzig',
                                                                            'type' => 'string'
                                                                        ],
                                                                        'layout' => [
                                                                            'default' => NULL,
                                                                            'type' => 'string'
                                                                        ],
                                                                        'id' => [
                                                                            'default' => NULL,
                                                                            'type' => 'string'
                                                                        ],
                                                                        '_class' => [
                                                                            'default' => NULL,
                                                                            'type' => 'string'
                                                                        ],
                                                                        'play' => [
                                                                            'default' => NULL,
                                                                            'type' => 'string'
                                                                        ],
                                                                        'transpose' => [
                                                                            'default' => NULL,
                                                                            'type' => 'string'
                                                                        ]
                                                                   ]
                                                                   )
                                             )
                ) error_log("Music Sheet Viewer ERROR : Cannot register block type: ".self::MSV_BLOCK);
        }
        
        /**
		 * Activates the plugin
		 */
		public function activate()
		{
            // Uncomment next line in case any other deactivation failed keeping the shortcode
            // remove_shortcode (self::MSV_SHORTCODE);
            if ( shortcode_exists( self::MSV_SHORTCODE ) )
                die('Plugin NOT activated: shortcode '.self::MSV_SHORTCODE.' already used by another plugin. Please deactivate it in order to use Music Sheet Viewer plugin');
            
            //Used to create the options in database (wp_options table) with their default value so that get_option always returns the default value if unset the first time
            //If option already exist, will not override the value
            if (get_option(MSV_INSTR) === false)
            { // Workaround as there is no plugin activation at upgrade time
                add_option(MSV_INSTR, MSV_INSTR_DFLT);
                add_option(MSV_MXL_UNZIP_METH, MSV_MXL_UNZIP_METH_DFLT);
            }
            // should have been only:
            /*
            add_option(MSV_INSTR, MSV_INSTR_DFLT);
            add_option(MSV_MXL_UNZIP_METH, MSV_MXL_UNZIP_METH_DFLT);
             */
		}
        
		/**
		 * Deactivates the plugin
		 */
		public function deactivate()
		{
            remove_shortcode (self::MSV_SHORTCODE);
		}

        /**
         * Adds Javascript files in the page header
         */
        public function hook_javascript() {
            global $post;
            if (is_a( $post, 'WP_Post' )
               && (has_shortcode( $post->post_content, self::MSV_SHORTCODE)
                   || has_block( self::MSV_BLOCK, $post->post_content))) // as the class is instantiated whatever the page, we only insert stuff in the header if there is a msv shortcode or block
            {
                //we use the shortcode as handler
                wp_enqueue_script (self::MSV_SHORTCODE, plugins_url('/js/verovio-toolkit-light.js', __FILE__ ), array(), self::VRV_VERSION);
                wp_enqueue_script ('msv', plugins_url('/js/msv.js', __FILE__ ), array('jquery'), self::MSV_VERSION);

                // For setting filePackagePrefixURL in MidiPlayer of midiplayer.js. Useful to locate <instrument>.data
                // It's in the js directory of msv plugin. plugin_dir_url has a trailing slash
                wp_add_inline_script( self::MSV_SHORTCODE, 'var MidiPlayer_filePackagePrefixURL = "'.plugin_dir_url(__FILE__).'js/instruments/";' );
                wp_add_inline_script( self::MSV_SHORTCODE, 'var '.self::MSV_SHORTCODE.'_ajax_url = "'.admin_url('admin-ajax.php').'";' );
                
                //midiplayer styles must be inserted unconditionnally in the header so that they can be overriden
                wp_enqueue_style ('midiplayer', plugins_url('/js/midiplayer.css', __FILE__ ), array(), self::MSV_VERSION);
            }
        }

        /**
         * Strips tags
         */
        protected function strip_selected_tags($text, $tags = array())
        {
            foreach ($tags as $tag)
            {
                $strs = array( '/<' . $tag . '(\s)*?\/?>/', '/<\/' . $tag . '(\s)*?>/');
                $text = preg_replace($strs, '', $text);
            }

            return $text;
        }

        /**
         * Processes the attributes
         */
        protected function processParams ($atts)
        {
            //Set defaults
            $attributes = shortcode_atts( array(
                                                //'attr_1' => 'attribute 1 default',
                                                self::MSV_OPTION_FORMAT => self::MSV_FORMAT_PAE
                                                ,self::MSV_OPTION_FONT => self::MSV_FONT_LEIPZIG
                                                ,self::MSV_OPTION_LAYOUT => NULL
                                                ,self::MSV_OPTION_FILE => NULL
                                                ,self::MSV_OPTION_ID => NULL
                                                ,self::MSV_OPTION_CLASS => NULL
                                                ,self::MSV_OPTION_PLAY => NULL
                                                ,self::MSV_OPTION_TRANSP => NULL
                                                // ...etc
                                                ), $atts );
            
            // As Block attributes may be empty strings (and the same shortcode attributes null), compatibility code
            if (isset($attributes[self::MSV_OPTION_LAYOUT])
                && $attributes[self::MSV_OPTION_LAYOUT] == '')
                $attributes[self::MSV_OPTION_LAYOUT] = NULL;
            if (isset($attributes[self::MSV_OPTION_PLAY])
                && $attributes[self::MSV_OPTION_PLAY] == '')
                $attributes[self::MSV_OPTION_PLAY] = NULL;
            if (isset($attributes[self::MSV_OPTION_FILE])
                && $attributes[self::MSV_OPTION_FILE] == '')
                $attributes[self::MSV_OPTION_FILE] = NULL;
            if (isset($attributes[self::MSV_OPTION_ID])
                && $attributes[self::MSV_OPTION_ID] == '')
                $attributes[self::MSV_OPTION_ID] = NULL;
            if (isset($attributes[self::MSV_OPTION_CLASS])
                && $attributes[self::MSV_OPTION_CLASS] == '')
                $attributes[self::MSV_OPTION_CLASS] = NULL;
            if (isset($attributes[self::MSV_OPTION_TRANSP])
                && $attributes[self::MSV_OPTION_TRANSP] == '')
                $attributes[self::MSV_OPTION_TRANSP] = NULL;
            
            $this->myformat = $attributes[self::MSV_OPTION_FORMAT];
            $this->myfont = $attributes[self::MSV_OPTION_FONT];
            $this->mylayout = $attributes[self::MSV_OPTION_LAYOUT];
            $this->myfile = $attributes[self::MSV_OPTION_FILE];
            $this->myid = $attributes[self::MSV_OPTION_ID];
            $this->myclass = $attributes[self::MSV_OPTION_CLASS];
            $this->myplay = $attributes[self::MSV_OPTION_PLAY];
            $this->mytransp = $attributes[self::MSV_OPTION_TRANSP];
            
            // Check params
            
            if (!in_array($this->myformat, self::MSV_FORMATS))
                throw new Exception(self::MSV_OPTION_FORMAT.' '.$this->myformat.' is unknown');
            
            if (!in_array($this->myfont, self::MSV_FONTS))
                throw new Exception(self::MSV_OPTION_FONT.' '.$this->myfont.' is unknown');
            
            if (isset($this->mylayout) && !in_array($this->mylayout, self::MSV_LAYOUTS))
                throw new Exception(self::MSV_OPTION_LAYOUT.' '.$this->mylayout.' is unknown');
            
            if (isset($this->myplay) && !in_array($this->myplay, self::MSV_PLAYS))
                throw new Exception(self::MSV_OPTION_PLAY.' '.$this->myplay.' is unknown');
            
            // Check inter-params
            
            if (isset($this->myfont)
                && $this->myfont == self::MSV_FONT_ROLL
                && isset($this->myplay))
                throw new Exception(self::MSV_OPTION_FONT.' '.self::MSV_FONT_ROLL.' is incompatible with '.self::MSV_OPTION_PLAY.'='.$this->myplay. ' option');
            
            // scripts for playing enqueued only if the shortcode has a play option
            // As these scripts libs are enqueued in the body, forces to use jQuery(document).ready(function() to wait that page is loaded the page BEFORE executing the generated scripts
             if (isset($this->myplay)) {
                wp_enqueue_script ('msvplayer', plugins_url('/js/msvplayer.js', __FILE__ ), array(), self::MSV_VERSION);
                wp_enqueue_script ('wildwebmidi', plugins_url('/js/instruments/'.get_option(MSV_INSTR).'.js', __FILE__ ), array(), self::MSV_VERSION);
                wp_enqueue_script ('midiplayer', plugins_url('/js/midiplayer.js', __FILE__ ), array('wildwebmidi', self::MSV_SHORTCODE), self::MSV_VERSION); // dependency added to be sure that var MidiPlayer_filePackagePrefixURL is set before midiplayer.js is inserted
            }
        }

        /**
         * Processes header of the generated JavaScript code
         */
        protected function genHeader ()
        {
            $head = '
            <span style="color:#FFFFFF;font-size:0.8em">'.get_class($this).' '.self::MSV_VERSION.'</span>
            ';
            
            if (!isset($this->myclass))
                $head .= '<div id="'.$this->uniqueid.'"></div>';
            else
                $head .= '<div id="'.$this->uniqueid.'" class="'.$this->myclass.'"></div>';

            if (isset($this->myplay))
            {
                $head .= <<<HEADER_P1
                
              <div id="player$this->uniqueid"></div>
HEADER_P1;
            }
 
            $head .= <<<HEADER_P2
            
            <script type="text/javascript">
            var msv$this->uniqueid;
            var player$this->uniqueid;
HEADER_P2;
                                   
            if (isset($this->myplay))
                $head .= '
                jQuery(document).ready(function(){';
                                   
            return $head;
        }
                                   
        /**
         * Processes trailer of the generated JavaScript code
         */
        protected function genTrailer ()
        {
            $trail = '';

            if (isset($this->myplay))
            {
                if ($this->myplay == self::MSV_PLAY_AUTO || $this->myplay == self::MSV_PLAY_AUTOHIGH)
                // played at load time
                // We call directly the play method after 3s
                    $trail .= '
                                   setTimeout(function() {player'.$this->uniqueid.'.getPlayer().play();}, 3000);';
                $trail .= '
                });';
            }

            $trail .='
            </script>';
            
            return $trail;
        }

        /**
         * Processes generated JavaScript code for inline music code
         */
        protected function genInlineCase ($content)
        {
                $outputvardata = '';
            
                switch ($this->myformat)
                {
                            case self::MSV_FORMAT_PAE:
                            case self::MSV_FORMAT_ABC:
                                   // remove all HTML tags, not expected in pae format
                                   $escapedstr = wp_strip_all_tags( $content );
                                   
                                   // According to your WordPress installation language set in the wp-config.php file, WordPress may convert the "straight" quotes
                                   // into the opening and closing “curly” quotation marks
                                   // So we must retransform them if any:
                        
                                    // If ' has been converted to &lsquo; (opening single quote) or &rsquo; (closing single quote) then we must translate it again into '
                                   $strs1 = array( "&lsquo;", "&rsquo;");
                                   $escapedstr = str_replace($strs1,"'", $escapedstr);
                        
                                   // If '' has been converted to &laquo; (opening double quote) or &raquo; (closing double quote), or if », then we must translate it again into ''
                                   $strs2 = array( "&laquo;", "&raquo;", "»");
                                   $escapedstr = str_replace($strs2,"''", $escapedstr);
                        
                                   // Convert remaining HTML entities (eg &nbsp; ...) to characters
                                   $escapedstr = html_entity_decode($escapedstr);
                                   
                                   // Replace all end of lines by \n\ for Verovio
                                   $escapedstr = json_encode($escapedstr);
                                   
                                   // Warning here $escapedstr is already surrounded by " due to json_encode
                                   $outputvardata =  '
                    var data = '.$escapedstr.';';
                                   
                            break;
                        
                            case self::MSV_FORMAT_MEI:
                                   /* Strip all HTML tags that are in the input content.
                                    As it's an HTML page, it contains <br />, plus beginning of line, end of line
                                    */
                                   $tags = array( 'p', 'br', 'script');
                                   $escapedstr = $this->strip_selected_tags($content, $tags);
                                   
                                   // Replace all end of lines by \n\ for Verovio
                                   $escapedstr = json_encode($escapedstr);
                                   
                                   // Warning here data is surrounded by ' as there are " in mei code
                                   $outputvardata =  "
                    var data = '".$escapedstr."';";
                                   
                            break;
                                   
                            case self::MSV_FORMAT_XML:
                                   /* Strip all HTML tags that are in the input content.
                                    As it's an HTML page, it contains <br />, plus beginning of line, end of line
                                    */
                                   $tags = array( 'p', 'br', 'script');
                                   $escapedstr = $this->strip_selected_tags($content, $tags);
                                   
                                   // remove all visible characters front/back of the content
                                   $escapedstr = trim($escapedstr);
                                   
                                   // Replace all end of lines by \n\ for Verovio
                                   $escapedstr = preg_replace('/[\r\n]+/', '\\n\\', $escapedstr);
                                   
                                   //Remove trailing blanks between tags
                                   $escapedstr = preg_replace("/\s+</", "<", $escapedstr);
                                   
                                   // Warning here data is surrounded by ' as there are " in xml code
                                   $outputvardata =  "
                    var data = '".$escapedstr."';";
                                   
                            break;
                    } // switch
            
            return $outputvardata;
        }

        /**
         * Processes generated JavaScript code for the render data part (at a given scale, with a given font)
        */
        protected function genRenderDataPart ($data, $iscompressed, $scale, $font)
        {
            // scale is no longer used as svgViewBox is used
            //The options affecting the layout (e.g., pageHeight, or ignoreLayout) cannot be modified when rendering a page and reloading the data if necessary for this.
            // Discovered that if the options must be changed, it must be done BEFORE the data is reloaded
                $renderdpart = <<<RDP0

                msv$this->uniqueid = new Msv($data, $iscompressed,
                                                    {
                                                        font: $font,
                                                        scale:$scale,
                                                        svgViewBox: true,
RDP0;
                                                        
                if ($this->mylayout == 'justified') // We want a size that maximizes the space and all on a line
                $renderdpart .= '
                                          breaks:"none",';
                else
                $renderdpart .= '
                                          breaks:"auto",';
                
                $renderdpart .= '
                                          transpose:"'.$this->mytransp.'",';
            
                $renderdpart .= <<<RDP1
                                              
                                          adjustPageHeight: 1
                                          },
                                          "$this->uniqueid");
RDP1;
            return $renderdpart;
        }

       /**
         * Processes generated JavaScript code for the rendering part of the javascript code
        */
        protected function genRenderingPart ($data, $iscompressed = 'false')
        {
                $bodypart = '';
            
                $bodypart .= $this->genRenderDataPart ($data, $iscompressed, 100, '"'.$this->myfont.'"');

                if (isset($this->myfont) && $this->myfont == self::MSV_FONT_ROLL)
                    $bodypart .= '
                                msv'.$this->uniqueid.'.rollPage();';
                else
                if (isset($this->myplay))
                {
                    $bodypart .= '
                    player'.$this->uniqueid.' = new MsvPlayer("player'.$this->uniqueid.'", '.(($this->myplay == self::MSV_PLAY_HIGHLIGHT || $this->myplay == self::MSV_PLAY_AUTOHIGH) ? 'true' : 'false').', msv'.$this->uniqueid.');';
                }
            
                return $bodypart;
        }
                                   
        /**
         * Processes generated JavaScript code for file case
         */
        protected function genFileCase ()
        {
            $serverside = true; //default
            $mxlfile = !(strlen($this->myfile)<= 4 || strrpos($this->myfile, ".mxl", strlen($this->myfile)-4) === FALSE);
            $mxlfront = (get_option(MSV_MXL_UNZIP_METH) !== MSV_MXL_UNZIP_METH_DFLT);
            
            if (strpos($this->myfile,'/') === 0) // local file
            {
                if (false==file(ABSPATH .$this->myfile))
                    throw new Exception('local file '.ABSPATH .$this->myfile.' does not exist');
                
                if ($mxlfile === false
                    //not mxl: frontside (which is default), but must be reverted if serverside set to Y in that case
                     || $mxlfront === true)
                    $serverside = false;
            }
            else { //remote file
                // If the URL contains whitespaces
                $this->myfile = str_replace(' ', '%20', $this->myfile);
                
                //overriding the default stream context
                stream_context_set_default( [
                                           'ssl' => [
                                           'verify_peer' => false,
                                           'verify_peer_name' => false,
                                           ],
                                           ]);
                
                $file_headers = get_headers($this->myfile);
                if ($file_headers === FALSE)
                    throw new Exception('remote file '.$this->myfile.' is not a valid URL');
                else {
                    $http_return = $file_headers[0];
                    
                    if(!strpos($http_return,"200"))
                        throw new Exception('remote file '.$this->myfile.' does not exist');
                }
            }

            // success function called asynchronously as soon as the file has been loaded
            $filecase = '
            jQuery.ajax({';
            
            if ($serverside)
            { // ajax server-side, json returned with status code
               $filecase .= '
                   url: '.self::MSV_SHORTCODE.'_ajax_url
                   , data: { action : "readfile", url: "'.$this->myfile.'" }
                   , type: "post"
                   , dataType: "json"
                   , success: function(result) {
                                if (result.status == "success")
                        {'
                .$this->genRenderingPart('result.data')
                .'
                                }
                                else
                                {
                                    console.log("Music Sheet Viewer ERROR : "+result.data);
                                }
                    }';
            }
            else
            { // ajax client-side, only data returned
                if ($mxlfile === true && $mxlfront === true)
                {
                   $filecase .= '
                    url: "'.$this->myfile.'"
                    , xhrFields: { responseType: "arraybuffer" }
                    , success: function(data) {'
                    .$this->genRenderingPart('data', 'true');
                }
                else {
                    $filecase .= '
                    url: "'.$this->myfile.'"
                    , dataType: "text"
                    , success: function(data) {'
                   .$this->genRenderingPart('data');
                }
                
                $filecase .= '
                                }';
            }
                   
            $filecase .='
                   , error: function(jqXHR, textStatus, errorThrown) {
                        console.log("Music Sheet Viewer ERROR : loading file '.$this->myfile.' : "+textStatus+","+errorThrown);
                    }
            });';
            
            return $filecase;
        }

        /**
         * Reads a mxl MusicXML compressed file
         */
        protected function read_mxl_file($url)
        {
            $localfile = (parse_url($url, PHP_URL_HOST) == parse_url(site_url(), PHP_URL_HOST));
            $local_zip_path = parse_url($url, PHP_URL_PATH);
            $mxl_file = '';
            
            if ($localfile)
                // no need to copy locally
                $mxl_file = ABSPATH . $local_zip_path;
            else {
                str_replace('%20', ' ', $local_zip_path); // If the origin URL had %20, the local file must have blanks 
                $mxl_file = get_temp_dir() . basename($local_zip_path);
                
                if (!copy($url, $mxl_file))
                    throw new Exception('Failed to copy Zip from ' . $url . ' to ' . $mxl_file);
            }
            
            $zip = new ZipArchive;
            $containerfound = false;
            $xmlfilecontent = '';
            
            $res = $zip->open($mxl_file);
            if ($res === TRUE)
            {
                for($i = 0; $i < $zip->numFiles; $i++)
                {
                    $ithnameindex = $zip->getNameIndex($i);
                    if (strlen($ithnameindex)<= 4 || (strrpos($ithnameindex, ".xml", strlen($ithnameindex)-4) === FALSE
                        && strrpos($ithnameindex, ".musicxml", strlen($ithnameindex)-9) === FALSE))
                        ;// ignore
                    else
                        if ($ithnameindex === "META-INF/container.xml")
                            $containerfound = true;
                        else { //Here to avoid parsing META-INF/container.xml, we suppose that the only .xml file found besides container.xml is the score
                            $xmlfilecontent = $zip->getFromIndex($i);
                        }
                }
                $zip->close();
                
                if (!$localfile)
                    unlink($mxl_file);
                
                if (!$containerfound)
                    throw new Exception('Ill-formed mxl '.$url. ' META-INF/container.xml file not found');
                else if ($xmlfilecontent == '')
                    throw new Exception('Ill-formed mxl '.$url. ' xml file not found');
                else return $xmlfilecontent;
            }
            else
            {
                if (!$localfile)
                    unlink($mxl_file);
                
                throw new Exception('Error reading zip-archive, '.$url.' err='.$this->zipmessage($res));
            }
        } // read_mxl_file

        /**
         * Reads a score file server-side
         */
        public function read_score_file()
        {
            if (isset($_POST['url']))
            {
                $url = $_POST['url'];
                $array_return['status'] = 'success';
                if (strlen($url)<= 4 || strrpos($url, ".mxl", strlen($url)-4) === FALSE)
                {
                    $array_return['data'] = file_get_contents($url);
                    if ($array_return['data'] === false)
                    {
                        $err = error_get_last();
                        $array_return['data'] = $err['message'];
                        $array_return['status'] = 'error';
                    }
                }
                else {
                    //mxl - ZIP
                    if (strpos($url,'/') === 0) // local file
                        $url = ABSPATH.$url;
                            
                    try {
                        $array_return['data'] = $this->read_mxl_file($url);
                    }
                    catch (Exception $e) {
                        $array_return['data'] = $e->getMessage();
                        $array_return['status'] = 'error';
                    }
                }
                
                echo json_encode($array_return);
            }
            
            wp_die();
        }

        /**
         * Errors for zip
         */
        protected function zipmessage($code)
        {
            switch ($code)
            {
                case ZipArchive::ER_EXISTS:
                    return 'File already exists.';
                    
                case ZipArchive::ER_INCONS:
                    return 'Zip archive inconsistent.';
                    
                case ZipArchive::ER_INVAL:
                    return 'Invalid argument.';
                    
                case ZipArchive::ER_MEMORY:
                    return 'Malloc failure.';
                    
                case ZipArchive::ER_NOENT:
                    return 'No such file.';
                    
                case ZipArchive::ER_NOZIP:
                    return 'Not a zip archive.';
                    
                case ZipArchive::ER_OPEN:
                    return "Can't open file.";
                    
                case ZipArchive::ER_READ:
                    return 'Read error.';
                    
                case ZipArchive::ER_SEEK:
                    return 'Seek error.';
            }
        }

        /**
         * Callback started from the Block editor.
         * Just a wrapper to process_shortcode
         */
        public function process_block($atts)
        {
            if (!isset($atts[ 'content' ]))
                $atts[ 'content' ] = '';
            if (isset($atts[ '_class' ])) // doesn't fit as js attribute
                $atts[ 'class' ] = $atts[ '_class' ];
    
            return $this->process_shortcode($atts, $atts[ 'content' ]);
        }

        /**
         * This is THE function that processes the shortcode
         */
        public function process_shortcode ($atts, $content = "")
        {
            // Check parameters
            try {
                $this->processParams ($atts);
                
                if ($content != '' && isset($this->myfile))
                    throw new Exception('Content must be empty if file parameter is set');
                
                if (strlen($content) > self::MSV_MAX_CONTENT_LENGTH)
                    throw new Exception('Content length is greater than '.self::MSV_MAX_CONTENT_LENGTH.'. Please use a file parameter instead.');
                
                // if self-closing tag [pn_msv/], return ''
                if (strlen($content) == 0 && !isset($this->myfile))
                    return '';

                if (!isset($this->myid)){
                    $this->uniqueid = rand();
                }
                else {
                    $this->uniqueid = $this->myid;
                }
                
                $output = $this->genHeader();
                
                if(!isset($this->myfile)) // score is inline in the shortcode
                {
                    $output .= $this->genInlineCase ($content).$this->genRenderingPart('data');
                }
                else //Score is in a separate file
                {
                    $output .= $this->genFileCase ();
                }
                
                $output .= $this->genTrailer();
                
                return $output;
            }
            catch (Exception $e) {
                return 'Music Sheet Viewer ERROR : '.$e->getMessage();
            }
        } // END function process_shortcode
    } // END class MusicSheetViewerPlugin

    /**
     * uninstall the plugin : must be a static function
     */
    function msv_uninstall()
    {
        // Uninistall must remove settings
        //https://wordpress.stackexchange.com/questions/24600/how-can-i-delete-options-with-register-uninstall-hook
        delete_option(MSV_INSTR);
        delete_option(MSV_MXL_UNZIP_METH);
    }
} // END if(!class_exists('MusicSheetViewerPlugin'))

if(class_exists('MusicSheetViewerPlugin'))
{
    new MusicSheetViewerPlugin();
}

if(!class_exists('MusicSheetViewerSettings'))
{
 class MusicSheetViewerSettings
 {
    protected $options = array(
                               array("name" => "Instrument"
                                     ,"id" => MSV_INSTR
                                     ,"type" => "select"
                                     ),
                               array("name" => "MXL uncompression method"
                                     ,"id" => MSV_MXL_UNZIP_METH
                                     ,"type" => "radio"
                                     ,"options" => array("front" => "Front-end", "back" => "Back-end")
                                     )
                               );
    /**
     * Returns in an array, all instruments found in .data files in instruments subdir
     */
    protected function instruments($dir)
    {
        if(!is_dir($dir))
        {
            return false;
        }
        $dirhandle = opendir($dir);
        $instrs = array();
        while($entry = readdir($dirhandle))
        {
            if($entry!='.' && $entry!='..' && !is_dir($dir.'/'.$entry)
               && (preg_match('/([0-9]+)_(.+).data/', $entry, $matches) === 1)) // matches
            {
                $entryname= $matches[1].'_'.$matches[2]; // file (without extension)
                $instr = $matches[2]; // instr with _
                $label = str_replace('_',' ',$instr); // suitable label
                $instrs[$entryname] = $label;
            }
        }
        closedir($dirhandle);
        return $instrs;
    }
    
    public function __construct()
    {
        if (get_option(MSV_INSTR) === false)
        { // Workaround as there is no plugin activation at upgrade time
            add_option(MSV_INSTR, MSV_INSTR_DFLT);
            add_option(MSV_MXL_UNZIP_METH, MSV_MXL_UNZIP_METH_DFLT);
        }
        
        //init instruments combo
        foreach ($this->options as $i => $value)
        {
            if ($value['id'] == MSV_INSTR)
            {
                $this->options[$i]['options'] = $this->instruments(plugin_dir_path( __FILE__ ).'js/instruments');
                break;
            }
        }
        
        add_action('admin_menu', array($this,'add_settings_page'));
        add_action('admin_init', array($this,'register_settings'));
        add_action('admin_notices', array($this,'reset_defaults_notice'));
    }
    
    public function add_settings_page()
    {
        // Creates a page under Settings
        add_options_page('Music Sheet Viewer', // page title
                         'Music Sheet Viewer', // menu title
                         'manage_options',//capability
                         'msvoptions-group', //	The slug name to refer to this menu by
                         array($this, 'options_page')); //	The function to be called to output the content for this page.
    }
    
    public function register_settings()
    {
        //register our settings
        register_setting('msvoptions-group', MSV_INSTR);
        register_setting('msvoptions-group', MSV_MXL_UNZIP_METH);
    }

    /**
     * Displays a message for saying that defaults were reset
     */
    public function reset_defaults_notice()
    {
         global $pagenow;
         if ( $pagenow == 'options-general.php' )
         {
             if ( isset( $_POST['reset-defaults'] ) )
             {
                 echo '<div class="notice notice-success is-dismissible"><p><b>'
                 .esc_js( __( 'Settings reset to defaults.', 'music-sheet-viewer' ) )
                 .'</b></p></div>';
             }
         }
     }
     
    /**
     * Used to reset options to their default value. Only called when 'Reset to Defaults' button is hit
     */
    protected function reset_options()
    {
        update_option(MSV_INSTR, MSV_INSTR_DFLT);
        update_option(MSV_MXL_UNZIP_METH, MSV_MXL_UNZIP_METH_DFLT);
    }
    
    public function options_page()
    {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function( $ ) {
                               // Confirm pressing of the "Reset to Defaults" button
                               $("#reset-defaults").click(function(){
                                                        var areyousure = confirm("<?php echo esc_js( __( 'Are you sure you want to reset your settings to the defaults?', 'music-sheet-viewer' ) ); ?>");
                                                        if ( true != areyousure ) return false;
                                                        });
                               });
        </script>
        <div class="wrap">
        <h1>Music Sheet Viewer settings</h1>
        <?php
        $this->create_form($this->options);
        ?>
        </div>
        <?php
    }
    
    protected function create_opening_tag($value)
    {
        if (isset($value['name'])) {
            echo "<h2>" . $value['name'] . "</h2>\n";
        }
    }
    
    protected function create_section_for_radio($value)
    {
        $this->create_opening_tag($value);
        foreach ($value['options'] as $option_value => $option_text) {
            $checked = ' ';
            if (get_option($value['id']) == $option_value)
            {
                $checked = ' checked="checked" ';
            }
            
            echo '<div class="mnt-radio"><input type="radio" name="'.$value['id'].'" value="'.
            $option_value.'" '.$checked."/>".$option_text."</div>\n";
        }
    }
    
    protected function create_section_for_category_select($value) {
        $this->create_opening_tag($value);
        echo '<div class="wrap" id="'.$value['id'].'" >'."\n";
        echo "<select id='".$value['id']."' name='".$value['id']."'>\n";
        foreach ($value['options'] as $option_value => $option_text) {
            $selected = ' ';
            if (get_option($value['id']) == $option_value)
            {
                $selected = ' selected ';
            }
            echo '<option value="'.$option_value.'"'.$selected.'>'.$option_text."</option>\n";
        }
        echo "</select>\n </div>";
    }
    
    public function create_form($options)
    {
        if ( isset( $_POST['reset-defaults'] ) )
        {
            $this->reset_options();
        }
        ?>
        <form method="POST" action="options.php">
        <?php
        settings_fields( 'msvoptions-group' );
        do_settings_sections( 'msvoptions-group' );
        
        foreach ($options as $value) {
            switch ( $value['type'] ) {
                case "radio":
                    $this->create_section_for_radio($value);
                    break;
                case "select":
                    $this->create_section_for_category_select($value);
                    break;
            }
        }
        
        submit_button( null, 'primary');
        ?>
        </form>
        <form method="POST">
        <?php
        submit_button( __( 'Reset to Defaults', 'music-sheet-viewer' ), 'primary', 'reset-defaults');
        ?>
        </form>
        <?php
    }
 } // END Class MusicSheetViewerSettings
}  //  if(!class_exists('MusicSheetViewerSettings'))

if(class_exists('MusicSheetViewerSettings')
   && is_admin())
{
    new MusicSheetViewerSettings();
}
