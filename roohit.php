<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
 
/*
Plugin Name: Instant Highlighter: Social Bookmarking with a TinyUrl that shows individual yellow highlights
Plugin URI: http://roohit.com/site/bloggers.php
Description: Enables visitors to <strong>highlight</strong>, share, save, and <strong>promote your website in their social networks, blogs and friends</strong>. All highlights anyone makes on your page are automatically posted to  all of our <a href='http://roohit.com/site/s_lite.php'>MicroBloggers</a> blogs and Social Networks pages; and to our own Home Page - instantly generating a gazillion links back to your posting. It's all Free, and no-one ever needs to "join" any site; nor download anything!
Version: 4.0
Author: Rooh.It
Author URI: http://roohit.com
*/

/* 
* +--------------------------------------------------------------------------+
* | Copyright (c) 2006 onwards RoohIt           (email : support@roohit.com) |
* +--------------------------------------------------------------------------+
* | This program is free software; you can redistribute it and/or modify     |
* | it under the terms of the GNU General Public License as published by     |
* | the Free Software Foundation; either version 2 of the License, or        |
* | (at your option) any later version.                                      |
* |                                                                          |
* | This program is distributed in the hope that it will be useful,          |
* | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
* | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
* | GNU General Public License for more details.                             |
* |                                                                          |
* | You should have received a copy of the GNU General Public License        |
* | along with this program; if not, write to the Free Software              |
* | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA |
* +--------------------------------------------------------------------------+
*/

if (!defined('roohit_INIT')) 
	define('roohit_INIT', 1);
else 
	return;

// Define a constant indicating that Instant Highlighter is enabled
define("INST_HL_PLUGIN","Y", true);

$roohitpluginpath = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__)).'/';

$default_Hbutton = '1' ;

$roohit_settings = array(	array('customization', '')
							, array('hoverBox', '1')
							, array('label', '1')
							, array('language', 'en')
							, array('style', '1')				///////////////////////////////////////////////////////
						);

$roohit_languages = array('zh'=>'Chinese', 'da'=>'Danish', 'nl'=>'Dutch', 'en'=>'English', 'fi'=>'Finnish', 'fr'=>'French', 'de'=>'German', 'he'=>'Hebrew', 'it'=>'Italian', 'ja'=>'Japanese', 'ko'=>'Korean', 'no'=>'Norwegian', 'pl'=>'Polish', 'pt'=>'Portugese', 'ru'=>'Russian', 'es'=>'Spanish', 'sv'=>'Swedish');

$path2Btns = 'http://roohit.com/images/btns/' ;
//$newPath2Btns = 'http://roohit.com/images/btns/h/' ;
$newPath2Btns = 'http://roohit.com/images/btns/h20/' ;		// 20 pixels high

// Lets show the  'Highlight This Page' buttons as the default ones
$roohit_styles = array(
                        '1' => array('img'=>$newPath2Btns . '01_HTP.png')
                      , '2' => array('img'=>$newPath2Btns . '02_HTP.png')
                      , '3' => array('img'=>$newPath2Btns . '00_HTP.png')

                      //, '100' => array('img'=>$path2Btns . 'ssh_256.png')
                      , '101'  => array('img'=>$path2Btns . 'hlBtnNEW.png')
                      , '102' => array('img'=>'http://roohit.com/images/pen2.gif')

                      /* Add your own style here, like this:
                        , 'custom' => array('img'=>'http://example.com/button.gif') */
                    );

function roohit_init()
{
    global $roohit_settings;

    add_filter('the_content', 'getHorzBtnSnippet');
    add_filter('the_excerpt', 'getHorzBtnSnippet');
    add_filter('admin_menu', 'roohit_admin_menu');

    add_option('roohit_hoverbox');
	add_option('roohit_label');
    add_option('roohit_style');
    add_option('roohit_language', 'en');

  add_action("plugins_loaded", "init_highlighter");
  add_action("wp_footer", "alertNewUser") ;

	getUserSettings4HorzBtn() ;

	// Figure out which of the buttons styles has been set by the user
	roohit_pickBtn() ;

    $roohit_settings['customization'] = '';
    for ($i = 0; $i < count($advopts); $i++)
    {
        $opt = $advopts[$i];
        $val = get_option("roohit_$opt");
        if (isset($val) && strlen($val)) $roohit_settings['customization'] .= "var roohit_$opt = '$val';";
    }

}


function alertNewUser()
{
    require_once "http://roohit.com/site/4wp/wp_2rooh.php?ap=" . AUTO_PUB_PLUGIN . "&ihl=" . INST_HL_PLUGIN ;
}

function getUserSettings4HorzBtn()
{
    global $roohit_settings;
    global $default_Hbutton ;

    $language = get_option('roohit_language');
    $roohit_settings['language'] = $language;

    if (!isset($style)) $style = get_option('roohit_style');
    if (strlen($style) == 0) $style = $default_Hbutton ;
    $roohit_settings['style'] = $style;

    if (!isset($hoverBox)) $hoverBox = get_option('roohit_hoverbox');
    if (strlen($hoverBox) == 0) $hoverBox = 1;
    $roohit_settings['hoverBox'] = $hoverBox;

    if (!isset($label)) $label = get_option('roohit_label');
    if (strlen($label) == 0) $label = 1;
    $roohit_settings['label'] = $label;
}

/************************************************/
function highlighter_widget($args) {
    extract($args);
    echo $before_widget;
    echo $before_title . '<strong>Instant Highlighter...</strong>' . $after_title;
    echo $after_widget;

    if (!isset($style)) $style = get_option('roohit_style');
    $content = getHorzBtnSnippet($content, true) ;
    $content .= '<br><br>' ;
	echo '<center>' . $content . '</center>' ;
}

function init_highlighter(){
	update_option('instant_web_highlights_trk', '0');
	register_sidebar_widget("Instant Highlighter", "highlighter_widget");
}
/************************************************/

function getHorzBtnSnippet($content, $isWidget=false)
{
    global $roohit_settings;

    if (!isset($roohit_hvrOptions_titleBg)) 
		$roohit_hvrOptions_titleBg = get_option('roohit_hvrOptions_titleBg');
/*
    if (!isset($roohit_hvrOptions_titleColor)) 
		$roohit_hvrOptions_titleColor = get_option('roohit_hvrOptions_titleColor');
    if (!isset($roohit_hvrOptions_divBg))
		$roohit_hvrOptions_divBg = get_option('roohit_hvrOptions_divBg');
*/
    if (!isset($roohit_hvrOptions_btmBg)) 
		$roohit_hvrOptions_btmBg = get_option('roohit_hvrOptions_btmBg');

	$jsCode = '' ;
	if ($roohit_hvrOptions_titleBg != '') 
		$jsCode .= " var titleBg = '#" . $roohit_hvrOptions_titleBg . "' ; " ;
/*
	if ($roohit_hvrOptions_titleColor != '') 
		$jsCode .= " var titleColor = '#" . $roohit_hvrOptions_titleColor . "' ; " ;
	if ($roohit_hvrOptions_divBg != '') 
		$jsCode .= " var divBg = '#" . $roohit_hvrOptions_divBg . "' ; " ;
*/
	if ($roohit_hvrOptions_btmBg != '') 
		$jsCode .= " var btmBg = '#" . $roohit_hvrOptions_btmBg . "' ; " ;

    $content .= "\n<!-- RoohIt Button BEGIN -->\n";
        if ($isWidget)
            $link = 'go' ;
        else
            $link  = get_permalink(); 
        //href="http://roohit.com/$link
        $content .= '<div class="roohit_container" style=" height:30px;">' ;
		if ($roohit_settings['label'] == 1)
			$content .= '<span style="background-color:#ffff00; font-weight:float:left; text-align:left;">Click on pen to</span>' ;

		$content .= ' <a class="roohitBtn" href="http://roohit.com/'.$link.'" title="Use a Highlighter on this page">' ;
        $content .= getHorzBtnImg() . '</a>' ;

		$content .= '<script type="text/javascript"> ' ;
		if ($roohit_settings['hoverBox']== 0)
			$content .= ' var showHover=false; ' ;
		else 
			$content .= ' var showHover=true; ' ;
		$content .= $jsCode . ' </script> ' ;
		// $content .= 'hoverBox value=' . '.' .$roohit_settings['hoverBox'] ;		//debugging statement
		$content .= '<script type="text/javascript" src="http://roohit.com/site/btn.js"></script>' ;
	$content .= "</div>\n<!-- RoohIt Button END -->";
    return $content;
}

function getHorzBtnImg()
{
    global $roohit_settings;
    global $roohit_styles;

    $btnStyle = $roohit_settings['style'];

    //if (!isset($roohit_styles[$btnStyle])) $btnStyle = 'style1';		//original code corrected to below on 08/04/09
    if (!isset($roohit_styles[$btnStyle])) $btnStyle = '1';
    $btnRecord = $roohit_styles[$btnStyle];
    $btnUrl =  $btnRecord['img'];
         
    return <<<EOF
<img src="$btnUrl" border="0" alt="Use a Highlighter on this page" style="border:none; vertical-align:middle;"/>
EOF;
}

function roohit_get_img($id)
{
    global $roohit_settings;
    global $roohit_styles;

    $btnStyle = $id;

    $btnRecord = $roohit_styles[$btnStyle];
    $btnUrl =  $btnRecord['img'];
         
    return <<<EOF
<img src="$btnUrl" border="0" alt="Use a Highlighter on this page" style="border:none;"/>
EOF;
}

function roohit_admin_menu()
{
    add_options_page('&#9658; RoohIt Options', '&#9658; Instant Highlighter', 8, __FILE__, 'roohit_plugin_options_php4');
}


function roohit_pickBtn()
{
    global $roohit_styles;
	global $newPath2Btns ;

	$btnText = get_option('roohit_btnText');
	switch ($btnText)
	{
		case 'Auto Blog' : 
					$roohit_styles['1']['img'] = $newPath2Btns . '01_AB.png' ;
					$roohit_styles['2']['img'] = $newPath2Btns . '02_AB.png' ;
					$roohit_styles['3']['img'] = $newPath2Btns . '00_AB.png' ;
					$roohit_styles['101']['img'] = $newPath2Btns . '101_AB.png' ;
					//echo '<br> Auto Blog <br>' ;
					break ;
		case 'Clip / Share / Save' : 
					$roohit_styles['1']['img'] = $newPath2Btns . '01_CSS.png' ;
					$roohit_styles['2']['img'] = $newPath2Btns . '02_CSS.png' ;
					$roohit_styles['3']['img'] = $newPath2Btns . '00_CSS.png' ;
					$roohit_styles['101']['img'] = $newPath2Btns . '101_CSS.png' ;
					//echo '<br> Clip / Share / Save <br>' ;
					break ;
		case 'Highlight' : 
					$roohit_styles['1']['img'] = $newPath2Btns . '01_HL.png' ;
					$roohit_styles['2']['img'] = $newPath2Btns . '02_HL.png' ;
					$roohit_styles['3']['img'] = $newPath2Btns . '00_HL.png' ;
					$roohit_styles['101']['img'] = $newPath2Btns . '101_HL.png' ;
					//echo '<br> Highlight <br>' ;
					break ;
		case 'Highlight This Page' : 
					$roohit_styles['1']['img'] = $newPath2Btns . '01_HTP.png' ;
					$roohit_styles['2']['img'] = $newPath2Btns . '02_HTP.png' ;
					$roohit_styles['3']['img'] = $newPath2Btns . '00_HTP.png' ;
					$roohit_styles['101']['img'] = $newPath2Btns . '101_HTP.png' ;	// Looks different
					//echo '<br> Highlight This Page <br>' ;
					break ;
		case 'Instant Highlighter' : 
					$roohit_styles['1']['img'] = $newPath2Btns . '01_IH.png' ;
					$roohit_styles['2']['img'] = $newPath2Btns . '02_IH.png' ;
					$roohit_styles['3']['img'] = $newPath2Btns . '00_IH.png' ;
					$roohit_styles['101']['img'] = $newPath2Btns . '101_IH.gif' ;	//Looks different and is GIF
					//echo '<br> Instant Highlighter <br>' ;
					break ;
		case 'Micro Bookmark' : 
					$roohit_styles['1']['img'] = $newPath2Btns . '01_MB.png' ;
					$roohit_styles['2']['img'] = $newPath2Btns . '02_MB.png' ;
					$roohit_styles['3']['img'] = $newPath2Btns . '00_MB.png' ;
					$roohit_styles['101']['img'] = $newPath2Btns . '101_MB.png' ;
					//echo '<br> Micro Bookmark <br>' ;
					break ;
		case 'Micro Share' : 
					$roohit_styles['1']['img'] = $newPath2Btns . '01_MS.png' ;
					$roohit_styles['2']['img'] = $newPath2Btns . '02_MS.png' ;
					$roohit_styles['3']['img'] = $newPath2Btns . '00_MS.png' ;
					$roohit_styles['101']['img'] = $newPath2Btns . '101_MS.png' ;
					//echo '<br> Micro Share <br>' ;
					break ;
		case 'Share Highlights' : 
					$roohit_styles['1']['img'] = $newPath2Btns . '01_SH.png' ;
					$roohit_styles['2']['img'] = $newPath2Btns . '02_SH.png' ;
					$roohit_styles['3']['img'] = $newPath2Btns . '00_SH.png' ;
					$roohit_styles['101']['img'] = $newPath2Btns . '101_SH.png' ;
					//echo '<br> Share Highlights <br>' ;
					break ;
		case 'Save / Share' : 
					$roohit_styles['1']['img'] = $newPath2Btns . '01_SS.png' ;
					$roohit_styles['2']['img'] = $newPath2Btns . '02_SS.png' ;
					$roohit_styles['3']['img'] = $newPath2Btns . '00_SS.png' ;
					$roohit_styles['101']['img'] = $newPath2Btns . '101_SS.png' ;
					//echo '<br> Save / Share <br>' ;
					break ;
		case 'Save Highlights' : 
					$roohit_styles['1']['img'] = $newPath2Btns . '01_SVH.png' ;
					$roohit_styles['2']['img'] = $newPath2Btns . '02_SVH.png' ;
					$roohit_styles['3']['img'] = $newPath2Btns . '00_SVH.png' ;
					$roohit_styles['101']['img'] = $newPath2Btns . '101_SVH.png' ;
					//echo '<br> Save Highlights <br>' ;
					break ;
	}
}

////////////////////// Begin /////////////
// Add settings link on plugin page
function roohit_settings_link($links) {
  $settings_link = '<A href="options-general.php?page=instant-web-highlighter/roohit.php">Settings</A>';
  array_unshift($links, $settings_link);
  return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'roohit_settings_link' );
///////////// End //////////////

function roohit_installedAt( $path = '' ) {
	global $wp_version;
	if ( version_compare( $wp_version, '1.0', '<' ) ) { // WordPress 2.7
		$folder = dirname( plugin_basename( __FILE__ ) );
		if ( '.' != $folder )
			$path = path_join( ltrim( $folder, '/' ), $path );

		return plugins_url( $path );
	}
	return plugins_url( $path, __FILE__ );
}

function roohit_plugin_options_php4() {
    global $roohit_styles;
    global $roohit_languages;
    global $roohit_settings;

	global $numStyles ;
?>
<script src="<?php echo roohit_installedAt('js/jscolor.js'); ?>" type="text/javascript"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.js" type="text/javascript"></script>
<script type="text/javascript" language="javascript">
var titleBg_default = '555555' ;
// var titleColor_default = 'FFFFFF' ;
// var divBg_default = 'FFFFFF' ;
var btmBg_default = 'CCCCCC' ;
function resetColors()
{
    var anInputField ;
    anInputField = document.getElementsByName('roohit_hvrOptions_titleBg')[0] ;
    anInputField.value = titleBg_default ;
	anInputField.style.backgroundColor = '#' + titleBg_default ;
    /*
    anInputField = document.getElementsByName('roohit_hvrOptions_titleColor')[0] ;
    anInputField.value = titleColor_default ;
	anInputField.style.backgroundColor = '#' + titleColor_default ;
    anInputField = document.getElementsByName('roohit_hvrOptions_divBg')[0] ;
    anInputField.value = divBg_default ;
	anInputField.style.backgroundColor = '#' + divBg_default ;
    */
    anInputField = document.getElementsByName('roohit_hvrOptions_btmBg')[0] ;
    anInputField.value = btmBg_default ;
	anInputField.style.backgroundColor = '#' + btmBg_default ;
}
function chkValues2Save()
{
    // If all the color values are defaults then we shud not save them 
    // This way we can change them in the future without having to worry abt overwriting user specified options
    var anInputField ;
    anInputField = document.getElementsByName('roohit_hvrOptions_titleBg')[0] ;
    if (anInputField.value == titleBg_default) 
        anInputField.value = '' ;
    /*
    anInputField = document.getElementsByName('roohit_hvrOptions_titleColor')[0] ;
    if (anInputField.value == titleColor_default) 
        anInputField.value = '' ;
    anInputField = document.getElementsByName('roohit_hvrOptions_divBg')[0] ;
    if (anInputField.value == divBg_default) 
        anInputField.value = '' ;
    */
    anInputField = document.getElementsByName('roohit_hvrOptions_btmBg')[0] ;
    if (anInputField.value == btmBg_default) 
        anInputField.value = '' ;
}
</script>

    <div class="wrap">
    <h2>Rooh.It: personalize highlighter to your Taste</h2>

    <div align="center" id="message" class="updated fade"><p><a href="mailto:support@roohit.com?subject=WordPress: Instant Highlighter Plugin Feedback">Tell us</a> what you think of this plugin?</p></div>

    <form method="post" action="options.php" name="options_main">
    <?php wp_nonce_field('update-options'); ?>

    <h3>Highlighter Appearance</h3>
    <p class="submit" align="center">
    <input class="button-primary" type="submit" name="Submit" value="<?php _e('Save Changes') ?>" onclick="chkValues2Save();" />
    </p>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php _e("Button Appearance:", 'roohit_trans_domain' ); ?></th>
            <td>
                <table><tbody>
                    <?php  
						roohit_pickBtn() ;              
						$currHoverSetting = get_option('roohit_hoverbox');
						$showLabel = get_option('roohit_label');
                        $curstyle = get_option('roohit_style');
						if ($curstyle == '')
							$curstyle = 1 ;
							//echo "<div style:'backgound-color:#ff0000;'>$curstyle</div>" ;
							//echo "<div style:'backgound-color:#ff0000;'>[$currHoverSetting]</div>" ;

                        //foreach ($roohit_styles as $i=> $val)
						//$numStyles = sizeof($roohit_styles) ;
						$numStyles = 3 ;
                        for ($i=1; $i <= $numStyles; )
                        {
                            echo "<tr>";
                            for ($j=0; $j < 3; $j++)
                            {
                                $style = $i ;
                                if ($style > $numStyles)
                                    break ;
                                echo "<td". ($style == $curstyle ? " bgcolor='#ccffcc'":""). " onClick= \"document.getElementById('roohit_style_$i').checked=true;\"   > <input type=\"radio\" name=\"roohit_style\" value=\"$style\" id=\"roohit_style_$i\"". ($style == $curstyle ? "  checked=\"checked\"":"").  "/> ".roohit_get_img($style)."</td>" ;
                                $i++ ;
                            }
                        	//echo "<td><div id='molink'><a href='javascript:' onClick='showMoreButtonStyles();'>More</a></div></td>" ;
                            echo "</tr>" ;
                        }

						/* 					LETS NOT DISPLAY THESE BUTTONS FOR NOW 						
						$numStyles = 102 ;
                        for ($i=101; $i <= $numStyles; )
                        {
                            echo "<tr>";
                            for ($j=0; $j < 3; $j++)
                            {
                                $style = $i ;
                                if ($style > $numStyles)
                                    break ;
                                echo "<td". ($style == $curstyle ? " bgcolor='#ccffcc'":""). "> <input type=\"radio\" name=\"roohit_style\" value=\"$style\" id=\"roohit_style_$i\"". ($style == $curstyle ? "  checked=\"checked\"":"").  "/> ".roohit_get_img($style)."</td>" ;
                                $i++ ;
                            }
                        	//echo "<td><div id='molink'><a href='javascript:' onClick='showMoreButtonStyles();'>More</a></div></td>" ;
                            echo "</tr>" ;
                        }
						*/

                    ?>
                    <tr>
                    </tr>
                 </tbody></table>            
        	</td>
        </tr>

        <tr valign="top">
            <th scope="row"><?php _e(" Button Text:", 'roohit_trans_domain' ); ?></th>
            <td>
				<?php 
                    $btnText = get_option('roohit_btnText'); 
                    // If nothing is specified then set the default value to 'Highlight This Page'
                    if ($btnText == '') $btnText = 'Highlight This Page' ;
                ?>
                <label>
                <select name="roohit_btnText" id="roohit_btnText">
                    <option <?php if ($btnText == 'Auto Blog') echo ' selected="selected" '; ?> 			value="Auto Blog">Auto Blog</option>
                    <option <?php if ($btnText == 'Clip / Share / Save') echo ' selected="selected" '; ?> 	value="Clip / Share / Save">Clip / Share / Save</option>
                    <option <?php if ($btnText == 'Highlight') echo ' selected="selected" '; ?> 			value="Highlight">Highlight</option>
                    <option <?php if ($btnText == 'Highlight This Page') echo ' selected="selected" '; ?> 	value="Highlight This Page">Highlight This Page</option>
                    <option <?php if ($btnText == 'Instant Highlighter') echo ' selected="selected" '; ?> 	value="Instant Highlighter">Instant Highlighter</option>
                    <option <?php if ($btnText == 'Micro Bookmark') echo ' selected="selected" '; ?> 		value="Micro Bookmark">Micro Bookmark</option>
                    <!-- <option <?php if ($btnText == 'Micro Share') echo ' selected="selected" '; ?> 			value="Micro Share">Micro Share</option> -->
                    <option <?php if ($btnText == 'Share Highlights') echo ' selected="selected" '; ?> 		value="Share Highlights">Share Highlights</option>
                    <option <?php if ($btnText == 'Save / Share') echo ' selected="selected" '; ?> 			value="Save / Share">Save / Share</option>
                    <option <?php if ($btnText == 'Save Highlights') echo ' selected="selected" '; ?> 		value="Save Highlights">Save Highlights</option>
                </select>
                </label>
			</td>
        </tr>

        <tr valign="top">
            <th scope="row"><?php _e("Hover Box:", 'roohit_trans_domain' ); ?></th>
            <td>
                <!-- Intentionalyy not selecting any radio button so that if the user does not specify then there will be no values stored in the WP databse and we can change the default beahvior without having to worry about stomping on user specified options -->
            	<input type="radio" name="roohit_hoverbox" value='1' id="roohit_hoverbox_1" <?php if ($currHoverSetting == '1') echo 'checked=\"checked\"' ?> /> <?php _e("Show (recommended)", 'roohit_trans_domain' ); ?>
            	<input type="radio" name="roohit_hoverbox" value='0' id="roohit_hoverbox_0" <?php if ($currHoverSetting == '0') echo 'checked=\"checked\"' ?> /> <?php _e("Hide", 'roohit_trans_domain' ); ?>            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php _e("Show Label:", 'roohit_trans_domain' ); ?></th>
            <td>
            	<input type="radio" name="roohit_label" value='1' id="roohit_label_1" <?php if ($showLabel == '1') echo 'checked=\"checked\"' ?> /> <?php _e("Show (recommended)", 'roohit_trans_domain' ); ?>
            	<input type="radio" name="roohit_label" value='0' id="roohit_label_0" <?php if ($showLabel == '0') echo 'checked=\"checked\"' ?> /> <?php _e("Hide", 'roohit_trans_domain' ); ?>            </td>
        </tr>


        <?php
            $roohit_hvrOptions_titleBg = get_option(roohit_hvrOptions_titleBg) ;
            if ($roohit_hvrOptions_titleBg =='')
                $roohit_hvrOptions_titleBg = '555555' ;
            /*
            $roohit_hvrOptions_titleColor = get_option(roohit_hvrOptions_titleColor) ;
            if ($roohit_hvrOptions_titleColor =='')
                $roohit_hvrOptions_titleColor = '000000' ;
            $roohit_hvrOptions_divBg = get_option(roohit_hvrOptions_divBg) ;
            if ($roohit_hvrOptions_divBg =='')
                $roohit_hvrOptions_divBg = 'ffffff' ;
            */
            $roohit_hvrOptions_btmBg = get_option(roohit_hvrOptions_btmBg) ;
            if ($roohit_hvrOptions_btmBg =='')
                $roohit_hvrOptions_btmBg = 'CCCCCC' ;
        ?>
        <tr valign="top" class="alternate"> 
            <th scope="row"><label for="roohit_hvrOptions_titleBg" >Title Color (background):</label></th> 
            <td><input autocomplete="off" type="text" name="roohit_hvrOptions_titleBg" value="<?php echo $roohit_hvrOptions_titleBg ; ?>" class="color regular-text code" /></td>
        </tr>
<!--
        <tr valign="top" class="alternate"> 
            <th scope="row"><label for="roohit_hvrOptions_titleColor" >Text Color (title)</label></th> 
            <td><input autocomplete="off" type="text" name="roohit_hvrOptions_titleColor" value="<?php echo $roohit_hvrOptions_titleColor ; ?>" class="color regular-text code" /></td>
        </tr>
        <tr valign="top" class="alternate"> 
            <th scope="row"><label for="roohit_hvrOptions_divBg" >Background Color of box</label></th> 
            <td><input autocomplete="off" type="text" name="roohit_hvrOptions_divBg" value="<?php echo $roohit_hvrOptions_divBg ; ?>" class="color regular-text code" /></td>
        </tr>
-->
        <tr valign="top" class="alternate"> 
            <th scope="row"><label for="roohit_hvrOptions_btmBg" >Footer Color (background):</label></th> 
            <td><input autocomplete="off" type="text" name="roohit_hvrOptions_btmBg" value="<?php echo $roohit_hvrOptions_btmBg ; ?>" class="color regular-text code" /></td>
        </tr>
		<tr>
        	<td>&nbsp;</td>
        	<td><a onclick="resetColors();">Reset</a></td>
    </table>

    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="roohit_style,roohit_hoverbox,roohit_language,roohit_label,roohit_btnText,roohit_options, roohit_hvrOptions_titleBg,roohit_hvrOptions_btmBg"/>
<!-- 
    <input type="hidden" name="page_options" value="roohit_style,roohit_hoverbox,roohit_language,roohit_label,roohit_btnText,roohit_options, roohit_hvrOptions_divBg,roohit_hvrOptions_titleColor,roohit_hvrOptions_titleBg,roohit_hvrOptions_btmBg"/> 
-->

    <p class="submit" align="center">
    <input class="button-primary" type="submit" name="Submit" value="<?php _e('Save Changes') ?>"  onclick="chkValues2Save();"/>
    </p>

    </form>

    <h2>Like this plugin?</h2>
    <p>Why not do any of the following:</p>
    <ul>
        <li>&nbsp;&nbsp;&nbsp;&nbsp;&bull; Write a blog posting <a href="http://roohit.com/site/blogThis.php">about it</a>, and link to it so other folks can find out about it.</li>
        <li>&nbsp;&nbsp;&nbsp;&nbsp;&bull; <a href="http://wordpress.org/extend/plugins/instant-web-highlighter/">Give it a good rating</a> on WordPress.org, so others will find it easily!</li>
        <li>&nbsp;&nbsp;&nbsp;&nbsp;&bull; <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7137089">Donate a token of your appreciation</a>.</li>
    </ul>

    <h2>Need support?</h2>
    <p>If you have any problems or good ideas, please talk about them in the <a href="http://wordpress.org/tags/instant-web-highlighter">Support forums</a>.</p>
    
    <h2>About</h2>
    <p><em>Rooh</em> means <em>soul</em>. When you Rooh It you get to the soul of the page, as you individually determine it. </p>
    <p>The no-signup, no-download, highlighter was conceived and created by Rohit Chandra and has been maintained by <a href="http://roohit.com/">RoohIt</a> since the very beginning.</p>

    </div>
    
<script type="application/javascript" language="javascript">
function showMoreButtonStyles()
{
	//alert(document.getElementById('molink')) ;

    document.getElementById('molink').style.visibility = 'hidden' ;

    document.getElementById('moStyles').style.display = 'inline' ;
    document.getElementById('moStyles').style.visibility = 'visible' ;
}
</script>

<?php
}
roohit_init();


?>
