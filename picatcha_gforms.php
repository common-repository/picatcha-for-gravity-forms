<?php
/*
Plugin Name: Picatcha for Gravity Forms
Plugin URI: http://www.picatcha.com
Description: This plugin adds the ease of use and security of Picatcha's Pix-Captcha to your Gravity Forms in WordPress. Why settle for a difficult to use text captcha when you have already invested so much in a fantastic form system?
Author: Sean Carey, Picatcha, Inc
Version: 1.0
Author URI: http://www.picatcha.com

Copyright (c) 2012 Picatcha -- http://picatcha.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/



//require the picatchalib
if (!function_exists('_picatcha_http_post')) {
  require_once('picatcha/picatchalib.php');
}

add_filter("gform_add_field_buttons", "add_pixcaptcha_field");
//add_action("gform_field_standard_settings","my_standard_settings",10 ,2);
add_filter('gform_field_type_title', 'gform_picatcha_title');

//adds in the representation for the form editor
add_action("gform_field_input" ,"example_pixcaptcha", 10, 5);

//adds the settings
add_action("gform_editor_js", "pixcatpcha_editor_script");

//custom settings
add_action("gform_field_standard_settings", "pixcaptcha_standard_settings", 10,2);

//advanced settings
add_action("gform_field_advanced_settings", "pixcaptcha_advanced_settings", 10,2);

//enqueue the picatcha script
add_action("gform_enqueue_scripts","pixcaptcha_enqueue_scripts", 10,2);

//validate the Pix-Captcha field
add_filter("gform_field_validation", "pixcaptcha_validate_form", 10, 4);

//Add Pix-Captcha Settings to the Forms Menu
add_filter("gform_addon_navigation", "add_pixcaptcha_menu_item");


function add_pixcaptcha_field($field_groups){
    //require_once('gforms_picatcha.js.php');
    
    //script to name the widget
    ?>
    <script type="text/javascript">
      function AddPixcaptchaField(){
        for(var i=0; i<form.fields.length; i++){
          if(form.fields[i].type == 'pixcaptcha'){
            alert("<?php _e("Only one PixCaptcha field can be added to the form.", "pixcaptcha");?>");
            return
          }
        }
        StartAddField('pixcaptcha');
      }
      
      function SetDefaultValues_pixcaptcha(field) {
          field.label = "PixCaptcha";
          field['displayOnly'] = true;
          field['noDuplicates'] = true;
          field['type'] = 'pixcaptcha';
          console.log(field);
          return field;
      }
    </script>
    <?php
    foreach($field_groups as &$group){
        if($group["name"] == "advanced_fields"){
            $group["fields"][] = array("class"=>"button", "value" => __("Pix-Captcha", "gforms_picatcha"), "onclick" => "AddPixcaptchaField()");
            break;
        }
    }
    return $field_groups;
}

//sets the title of the widget
function gform_picatcha_title($type) {
  if ($type == 'pixcaptcha')
    return __('Pix-Captcha Image Captcha', 'gforms_picatcha');
}

function example_pixcaptcha($input, $field, $value, $lead_id, $form_id){
  $settings = get_option('gforms_picatcha');
  if ($field["type"] == "pixcaptcha"){
    //Gravity Forms Editor
    //if($settings['pixcaptcha_live_view']){
      //$input = "This is where a live version of Pix-captcha would go";
    //}else{
      $input = "<img src='".plugins_url( 'pixcaptcha.png' , __FILE__ )."' />";
    //}
    
    
    
    //Public
    if(!IS_ADMIN){
      $input = picatcha_get_html($settings["public_key"], NULL, $field['field_pixcaptcha_format'], $field['field_pixcaptcha_color'], $field['field_pixcaptcha_poweredby'], $field['field_pixcaptcha_imgSize'], $field["field_pixcaptcha_noise_level"], $field["field_pixcaptcha_noise_type"]);
    }
  }
  
  return $input;
}

function pixcatpcha_editor_script(){
  ?>
  <script type='text/javascript'>
    //Add in the settings
    jQuery(document).ready(function($){
      fieldSettings["pixcaptcha"] = ".label_setting, .pixcaptcha_setting";
    });
    
    //load in the settings
    jQuery(document).bind("gform_load_field_settings", function(event, field, form){
      
      //theme color
      jQuery("#pixcaptcha_color").attr("value", field["field_pixcaptcha_color"]);
      
      //logo
      jQuery("#pixcaptcha_poweredBy").attr("checked", field["field_pixcaptcha_poweredby"]==true);
      
      //captcha format
      jQuery("#pixcaptcha_format").attr("value", field["field_pixcaptcha_format"]);
      
      //image size
      jQuery("#pixcaptcha_imgSize").attr("value", field["field_pixcaptcha_imgSize"]);
      
      //noise type
      jQuery("#pixcaptcha_noise_type").attr("value", field["field_pixcaptcha_noise_type"]);
      
      //noise level
      jQuery("#pixcaptcha_noise_level").attr("value", field["field_pixcaptcha_noise_level"]);
    });
    
  </script>
  <?php
}

// Adds custom settings to the standard (properties?) tab
function pixcaptcha_standard_settings($position, $form_id){
  
  if($position == 50){
    ?>
      <li class="pixcaptcha_setting field_setting">
        
        <div>Display Settings:</div>
        
        <label for="pixcaptcha_color" class="inline"><?php _e("Theme Color: (in hex)","pixcaptcha")?></label>
        <input type="text" id="pixcaptcha_color" value="#2a1f19" onchange="SetFieldProperty('field_pixcaptcha_color', this.value)"/><br />
        
        <label for="pixcaptcha_format" class="inline"><?php _e("Format:", "pixcaptcha")?></label>
        <select id="pixcaptcha_format" onchange="SetFieldProperty('field_pixcaptcha_format', this.value)">
          <option value="1">3x2</option>
          <option value="2">4x2</option>
          <option value="3">5x2</option>
          <option value="4">6x2</option>
        </select><br />
        
        <label for="pixcaptcha_imgSize" class="inline"><?php _e("Image Size", "pixcaptcha")?></label>
        <select id="pixcaptcha_imgSize" onchange="SetFieldProperty('field_pixcaptcha_imgSize',this.value)">
          <option value="50">50</option>
          <option value="60">60</option>
          <option value="75">75</option>
        </select><br />
        
        <input type="checkbox" id="pixcaptcha_poweredBy" onclick="SetFieldProperty('field_pixcaptcha_poweredby', this.checked)"/>
        <label for="pixcaptcha_poweredBy" class="inline"><?php _e("Show 'Powered by logo'", "pixcaptcha")?></label><br />
      </li>
    <?php
  }
}

// Adds custom settings to the advanced tab
function pixcaptcha_advanced_settings($position, $form_id){
  
  if($position == 50){
    ?>
      <li class="pixcaptcha_setting field_setting">
        
        <div>Display Settings:</div>
        
        <label for="pixcaptcha_noise_level" class="inline"><?php _e("Noise Level:", "pixcaptcha")?></label>
        <select id="pixcaptcha_noise_level" onchange="SetFieldProperty('field_pixcaptcha_noise_level',this.value)">
          <option value="0">Off</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5</option>
          <option value="6">6</option>
          <option value="7">7</option>
          <option value="8">8</option>
          <option value="9">9</option>
          <option value="10">10 - Maximum</option>
        </select><br />
        
        <label for="pixcaptcha_noise_type" class="inline"><?php _e("Noise Type:", "pixcaptcha")?></label>
        <select id="pixcaptcha_noise_type" onchange="SetFieldProperty('field_pixcaptcha_noise_type', this.value)">
          <option value="0">Random</option>
          <option value="1">Shadow</option>
          <option value="2">Pixelation</option>
        </select><br />
      </li>
    <?php
  }
}

function pixcaptcha_enqueue_scripts($form, $ajax){
  
  //cycle through the fields to see if pixcaptcha is being used
  
  foreach( $form['fields'] as $field){
    if( ($field['type']=='pixcaptcha') && (isset($field['field_pixcaptcha'] ) ) ){
      wp_enqueue_script("gform_pixcaptcha_script", "http://api.picatcha.com/static/picatcha.js");
      break;
    }
    
  }
}

function pixcaptcha_validate_form($result, $value, $form, $field){
  $settings = get_option('gforms_picatcha');
  if ($field['type'] == 'pixcaptcha'){
    
    //only check if the rest of the form is valid
    if($result["is_valid"]){
      
      //check if the pixcaptcha is correct
      $response = picatcha_check_answer($settings["private_key"],
      $_SERVER['REMOTE_ADDR'],
      $_SERVER['HTTP_USER_AGENT'], $_POST['picatcha']['token'], $_POST['picatcha']['r']);
      
      if ($response->is_valid == true){
        $result["is_valid"] = true;
      }
      else{
        //not sure if this is necessary..
        $result["is_valid"] = false;
        $result["message"] = "Error: ".$response->error;
      }
    }
  }
  return $result;
}

function add_pixcaptcha_menu_item($menu_items){
  $menu_items[] = array("name" => "picatcha_submenu", "label" => "Picatcha", "callback" => "picatcha_submenu_handler", "permission" =>"edit_posts");
  
  return $menu_items;
}

function picatcha_submenu_handler(){
  
  //First, check if settings have been posted, if so save them
  
  if(isset($_POST["gforms_picatcha"])){
    $settings = array(
      "public_key" => trim($_POST['pixcaptcha_public_key']),
      "private_key" => trim($_POST['pixcaptcha_private_key']),
      //"pixcaptcha_live_view" => $_POST['pixcaptcha_live_view'],
    );
    
    $message = __('Settings saved', 'pixcaptcha');
    update_option('gforms_picatcha', $settings);
  }else{
    //Get the settings
    $settings = get_option('gforms_picatcha');
    
    //if there is no settings...
    if (!$settings){
      //defaults
      $settings = array(
        "public_key" => "",
        "private_key" => "",
        //"pixcaptcha_live_view" =>false,
      );
    }
  }
  
  ?>
  
  <!-- <p>Settings: <?php //print_r($settings);?></p> -->
  <form method="POST">
    <h2><?php _e("Picatcha for Gravity forms Settings", "pixcaptcha"); ?></h2>
    <p>Picatcha&apos;s Pix-Captcha image CAPTCHA system provides better security and better user experience for your forms than existing text CAPTCHAs. Sign up at Picatcha&apos;s website to get your public and private keys, used to authenticate the image CAPTCHA with our servers. You can set the look and feel of the Pix-Captcha when you add it to your forms.</p>
    <input type="hidden" name="gforms_picatcha" value="1">
    <div class="wrap">
      <table class="form-table">
        <tr valign="top">
          <th scope="row">
            <label for="pixcaptcha_public_key">Public Key</label>
          </th>
          <td>
            <input type="text" id="pixcaptcha_public_key" name="pixcaptcha_public_key" value="<?php echo $settings["public_key"]; ?>" />
            <p>Enter the public key that you got from registering. If you need a public key, please sign up at <a href="http://www.picatcha.com/signup">picatcha.com/signup</a></p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="pixcaptcha_private_key">Private Key</label>
          </th>
          <td>
            <input type="text" id="pixcaptcha_private_key" name="pixcaptcha_private_key" value="<?php echo $settings["private_key"]?>" />
            <p>Enter the Private key that you got from registering. If you need a private key, please sign up at <a href="http://www.picatcha.com/signup">picatcha.com/signup</a></p>
          </td>
        </tr>
        
      </table>
      
      
    </div>

    
    <!-- <div>
      <label for="pixcaptcha_live_view">Live View in form editor:</label>
      <input type="checkbox" id="pixcaptcha_live_view" name="pixcaptcha_live_view" checked="<?php //echo $settings['pixcaptcha_live_view']==1?>"/><?php //echo $settings['pixcaptcha_live_view']==1?>
      
      <p>When checked, this will show a live preview of your changes in the Gravity form Editors. If not checked, then a static image is shown instead.</p>
    </div> -->
  
    <p class="submit">
      <input type="Submit" value="Save Settings" class="button-primary picatcha_settings_savebutton"/>
    </p>
    
  </form>
  
  <?php
}
?>