/**
 * Plugin Name:    Music Sheet Viewer
 * Plugin URI:     http://www.partitionnumerique.com/music-sheet-viewer-wordpress-plugin/
 * Description:    Allows you to display music sheet from its MusicXML, MEI, ABC, PAE.. code
 * Author:         Etienne Frejaville
 * Author URI:     http://www.partitionnumerique.com
 * License:        GPL3
 * License URI:    https://www.gnu.org/licenses/gpl-3.0.html
 
 Copyright Etienne Frejaville 2018, 2019, 2020, 2021, 2022
 
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

const {registerBlockType} = wp.blocks; //Blocks API
const {createElement} = wp.element; //React.createElement
const {__} = wp.i18n; //translation functions
const {TextControl, TextareaControl, SelectControl, Button, PanelBody} = wp.components; //WordPress form inputs and server-side renderer
//const {serverSideRender} = wp.serverSideRender;
const { InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;

registerBlockType( 'music-sheet-viewer/pn-msv', {
                  title: __( 'Music Sheet Viewer', 'music-sheet-viewer' ), // Block title : appears in block selection when / is striked
                  description: __( 'Allows you to display music sheet from its MusicXML, MEI, ABC, PAE.. code', 'music-sheet-viewer' ),
                  category:  'common',
                  icon : 'playlist-audio', // from https://developer.wordpress.org/resource/dashicons/
                  attributes:  {
                    content : {
                        default: '',
                        type : 'string'
                    },
                    format : {
                        default: 'pae',
                        type: 'string'
                    },
                    file : {
                        default: null,
                        type: 'string'
                    },
                    font : {
                        default: 'Leipzig',
                        type: 'string'
                    },
                    layout : {
                        default: null,
                        type: 'string'
                    },
                    id : {
                        default: null,
                        type: 'string'
                    },
                    _class : {
                        default: null,
                        type: 'string'
                    },
                    play : {
                        default: null,
                        type: 'string'
                    }
                  },
                  edit(props){
                    const attributes =  props.attributes;
                    const setAttributes =  props.setAttributes;
                  
                    function changeFont(font){
                        setAttributes({font});
                    }
                  
                    function changeFormat(format){
                        setAttributes({format});
                    }
                  
                    function changeLayout(layout){
                        setAttributes({layout});
                    }
                  
                    function changePlay(play){
                        setAttributes({play});
                    }
                  
                    function changeId(id){
                        setAttributes({id});
                    }
                  
                    function changeClass(_class){
                        setAttributes({_class});
                    }
                  
                    function changeFile(file){
                        setAttributes({file});
                    }
                  
                    function updateContent( content ) {
                        setAttributes( { content } );
                    }
                  
                    //Display block preview and UI
                    return createElement('div', {}, [
                                                     //preview will go here
                                                     /*createElement( wp.serverSideRender, {
                                                                   block: 'music-sheet-viewer/pn-msv',
                                                                   attributes: attributes
                                                                   } ),*/
                                                     //content will go here
                                                     createElement(TextareaControl,
                                                                    {
                                                                     onChange: updateContent,
                                                                     value: attributes.content,
                                                                     label: __( 'Enter inline score for Music Sheet Viewer here...', 'music-sheet-viewer' )
                                                                     }
                                                                     ),
                                                   //Block inspector
                                                     createElement(InspectorControls,
                                                                 {},
                                                                 [
                                                            createElement(PanelBody,
                                                                {title: __('Options', 'music-sheet-viewer'),
                                                                 initialOpen:true
                                                                }, [
                                                                  createElement(SelectControl,
                                                                                  {
                                                                                  value: attributes.format,
                                                                                  label: __( 'Format', 'music-sheet-viewer' ),
                                                                                  onChange: changeFormat,
                                                                                  options: [
                                                                                            {value: 'pae', label: 'pae'},
                                                                                            {value: 'mei', label: 'mei'},
                                                                                            {value: 'xml', label: 'xml'},
                                                                                            {value: 'abc', label: 'abc'}
                                                                                            ]
                                                                                  }),
                                                                  createElement(TextControl,
                                                                                {
                                                                                onChange: changeFile,
                                                                                value: attributes.file,
                                                                                label: __( 'File', 'music-sheet-viewer' )
                                                                                }
                                                                                ),
                                                                  createElement(MediaUploadCheck,null,
                                                                                //To make sure the current user has Upload permissions, you need to wrap the MediaUpload component into the MediaUploadCheck one.
                                                                                createElement(MediaUpload,
                                                                                              {
                                                                                                allowedTypes:["application/vnd.recordare.musicxml"
                                                                                                              // application/vnd.recordare.musicxml works for musicxml (.xml) and .mxl
                                                                                                              ,"text/xml" //mei, xml, musicxml
                                                                                                              , "text/plain" // abc, pae
                                                                                                              ],

                                                                                                onSelect:function(e){
                                                                                                    //Callback called when the media modal is closed after media is selected.
                                                                                                    //e = selected media
                                                                                                    changeFile(e.url.substr(e.url.indexOf('/wp-content')));
                                                                                                },
                                                                                                render:function(e){
                                                                                                    //A callback invoked to render the Button opening the media library.
                                                                                                    // The first argument of the callback is an object containing the following properties:
                                                                                                    // open: A function opening the media modal when called
                                                                                                    return createElement(Button, {isSecondary:true,onClick:e.open}, __( 'Select file', 'music-sheet-viewer' ));
                                                                                                }
                                                                                              })
                                                                                ),
                                                                    createElement(SelectControl,
                                                                                  {
                                                                                  value: attributes.font,
                                                                                  label: __( 'Font', 'music-sheet-viewer' ),
                                                                                  onChange: changeFont,
                                                                                  options: [
                                                                                            {value: 'Leipzig', label: 'Leipzig'},
                                                                                            {value: 'Bravura', label: 'Bravura'},
                                                                                            {value: 'Gootville', label: 'Gootville'},
                                                                                            {value: 'Petaluma', label: 'Petaluma'},
                                                                                            {value: 'Leland', label: 'Leland'},
                                                                                            {value: 'roll', label: 'roll'}
                                                                                            ]
                                                                                  }),
                                                                    createElement(SelectControl,
                                                                                  {
                                                                                  value: attributes.layout,
                                                                                  label: __( 'Layout', 'music-sheet-viewer' ),
                                                                                  onChange: changeLayout,
                                                                                  options: [
                                                                                            {value: null, label: null},
                                                                                            {value: 'justified', label: 'justified'}
                                                                                            ]
                                                                                  }),
                                                                    createElement(TextControl,
                                                                                  {
                                                                                  value: attributes.id,
                                                                                  label: __( 'Id', 'music-sheet-viewer' ),
                                                                                  onChange: changeId
                                                                                  }),
                                                                    createElement(TextControl,
                                                                                  {
                                                                                  value: attributes._class,
                                                                                  label: __( 'Class', 'music-sheet-viewer' ),
                                                                                  onChange: changeClass
                                                                                  }),
                                                                    createElement(SelectControl,
                                                                                  {
                                                                                  value: attributes.play,
                                                                                  label: __( 'Play', 'music-sheet-viewer' ),
                                                                                  onChange: changePlay,
                                                                                  options: [
                                                                                            {value: null, label: null},
                                                                                            {value: 'player', label: 'player'},
                                                                                            {value: 'auto', label: 'auto'},
                                                                                            {value: 'highlight', label: 'highlight'},
                                                                                            {value: 'autohigh', label: 'autohigh'}
                                                                                            ]
                                                                                  })
                                                                    ])
                                                                  ]
                                                                 )
                                                   ] )
                  },
                  save(){
                    return null;//save has to exist. This all we need
                  }
});
