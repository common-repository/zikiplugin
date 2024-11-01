<?php
/*
Plugin Name: ZikiPlugin (widget)
Plugin URI: http://blog.nicolargo.com/2007/09/plugin-ziki-pour-wordpress.html
Description: A simple plugin (widget) to display Ziki informations
Author: Nicolas Hennion
Version: 1.1
Author URI: http://blog.nicolargo.com/
*/

define(ZIKIURL, "http://www.ziki.com/fr/");
define(ZIKIIMGURL_PRE, "http://www.ziki.com/image/people/");
define(ZIKIIMGURL_POST, "/thumb");
define(ZIKIFAVICONURL, "http://www.ziki.com/files/locale/favicons/");
define(TWITTERURL, "http://twitter.com/statuses/friends_timeline.xml");

function widget_ZikiPlugin_init() {

	if ( !function_exists('register_sidebar_widget') )
		return;

		function widget_ZikiPlugin($args) {
		
			// "$args is an array of strings that help widgets to conform to
			// the active theme: before_widget, before_title, after_widget,
			// and after_title are the array keys." - These are set up by the theme
			extract($args);

			// These are our own options
			$options = get_option('widget_ZikiPlugin');
			$title = $options['title'];  // Title in sidebar for widget
			$name = $options['name'];  // Ziki name
			$tag_realname = $options['tag_realname'];  // Display first and last name ?
			$tag_baseline = $options['tag_baseline'];  // Display baseline ?
			$tag_profile = $options['tag_profile'];  // Display profile links ?
			$tag_fansfriends = $options['tag_fansfriends'];  // Display number of fans and friends ?
			$tag_stats = $options['tag_stats'];  // Display stats  ?
			$tag_twitter = $options['tag_twitter'];  // Display last Twitter baseline  ?
			$twitter_mail = $options['twitter_mail']; // Twitter mail (login)
			$twitter_password = $options['twitter_password']; // Twitter password

			// Output
			echo $before_widget . $before_title . $title . $after_title;

			// Get Ziki informations
			$xml_ziki_url = ZIKIURL.$name.".xml";
			if(($handle_xml = fopen($xml_ziki_url,"r")) == FALSE) {
        		die('Can not connect to Ziki');
    		}
    		// Get XML file from Ziki
			while (!feof($handle_xml)) {
    			$xml = fgets($handle_xml, 4096);
    			if (eregi("<url>(.*)</url>", $xml, $regs) && !$url_ziki) {
    				$url_ziki = $regs[1];
    			}
    			else if (eregi("<nickname>(.*)</nickname>", $xml, $regs)) {
    				$nickname = $regs[1];
    				$nickname_maj = ucfirst($nickname);
    			}
    			else if (eregi("<first_name>(.*)</first_name>", $xml, $regs)) {
    				$first_name = $regs[1];
    			}
    			else if (eregi("<last_name>(.*)</last_name>", $xml, $regs)) {
    				$last_name = $regs[1];
    			}
    			else if (eregi("<thumb>(.*)</thumb>", $xml, $regs)) {
    				$avatar = $regs[1];
    				// Avatar is now defined by the ZIKIIMGURL_PRE+$name+ZIKIIMGURL_POST variable
    			}
    			else if (eregi("<baseline>", $xml, $regs)) {
    				// Enter in the baseline section
    				$baseline .= "";
    				while (!eregi("</baseline>", $xml, $regs) && !feof($handle_xml)) {
    					$xml = fgets($handle_xml, 4096);
    					$baseline .= $xml;
    				}
    				// Extract the baseline from the CDATA
    				if (eregi("\<\!\[CDATA\[(.*)\]\]>", $baseline, $regs)) {
    					$baseline = $regs[1];
    				}
    			}
    			else if (eregi("<profiles>", $xml, $regs)) {
    				// Enter in the profiles section
    				while (!eregi("</profiles>", $xml, $regs) && !feof($handle_xml)) {
    					$xml = fgets($handle_xml, 4096);
    					if (eregi("<profile>", $xml, $regs)) {
		    				// Enter in the profile section
    						while (!eregi("</profile>", $xml, $regs) && !feof($handle_xml)) {
    							$xml = fgets($handle_xml, 4096);
				    			if (eregi("<name>(.*)</name>", $xml, $regs) && !feof($handle_xml)) {
				    				$profile_name = strtolower($regs[1]);
				    			}
				    			else if (eregi("<url>(.*)</url>", $xml, $regs)) {
				    				$profiles[$profile_name] = $regs[1];
				    			}
    						}
    					}
    				}
    			}
    			else if (eregi("<stats>", $xml, $regs)) {
    				// Enter in the profiles section
    				while (!eregi("</stats>", $xml, $regs) && !feof($handle_xml)) {
    					$xml = fgets($handle_xml, 4096);
		    			if (eregi("<technorati_rank>(.*)</technorati_rank>", $xml, $regs) && !feof($handle_xml)) {
		    				$stats_technorati_rank = $regs[1];
		    			}
		    			else if (eregi("<feedburner_circulation>(.*)</feedburner_circulation>", $xml, $regs)) {
		    				$stats_feedburner_circulation = $regs[1];
		    			}
		    			else if (eregi("<friends>(.*)</friends>", $xml, $regs)) {
		    				$stats_friends = $regs[1];
		    			}
		    			else if (eregi("<fans>(.*)</fans>", $xml, $regs)) {
		    				$stats_fans = $regs[1];
		    			}
    				}
    			}
			}
			// Define the avatar URL
			$avatar = ZIKIIMGURL_PRE.$name.ZIKIIMGURL_POST;
			fclose($handle_xml);

			// Twitter
			if ($tag_twitter && (strcmp($twitter_mail, "")) && (strcmp($twitter_password, ""))) {
    			// Get XML file from Twitter
				$curl_twitter = curl_init(TWITTERURL);
				curl_setopt($curl_twitter, CURLOPT_USERPWD, $twitter_mail.":".$twitter_password);
				curl_setopt($curl_twitter, CURLOPT_RETURNTRANSFER, 1); 
				curl_setopt($curl_twitter, CURLOPT_HEADER, 0);
				$twitter_result = curl_exec($curl_twitter);
				curl_close($curl_twitter);
				// Analyse Twitter XML file
				$twitter_result = preg_split("/\n/", $twitter_result);
				foreach ($twitter_result as $xml) {
	    			if (eregi("<text>(.*)</text>", $xml, $regs)) {
    					$twitter_text = $regs[1];
    					break;
    				}
				}
			}
			
			// Build the Zikiplugin HTML Code
			echo '<div class="zikiplugin-main" style="padding-top:5px; padding-left:10px; padding-right:10px">';

			echo '<a href="'.$url_ziki.'"><img src="'.$avatar.'" style="float:left"></a>';

			echo '<div class="zikiplugin-nickname" style="text-align:center; padding-top:5px">';
			echo '<a href="'.$url_ziki.'"><b>'.$nickname_maj."</b></a>";
			if ($tag_realname) {
				echo " (".$first_name." ".$last_name.")";
			}
			if ($tag_baseline) {
				echo "<br/><small><i>".$baseline."</i></small>";
			}			
			echo '</div>';

			if ($tag_profile) {
				echo '<div class="zikiplugin-profile" style="text-align:center; padding-top:10px">';
				echo '<a href="'.$url_ziki.'"><img src="'.ZIKIFAVICONURL.'ziki.com.ico"></a>';
				foreach ($profiles as $profile_name => $profile_url) {
					preg_match("/^(http:\/\/)?([^\/]+)/i", $profile_url, $matches);
					preg_match("/[^\.\/]+\.[^\.\/]+$/", $matches[2], $matches);
					$domain_name = $matches[0];
					$img_url = ZIKIFAVICONURL.$domain_name.".ico";
						echo '<a href="'.$profile_url.'"><img src="'.$img_url.'" alt="'.$domain_name.'"></a>';
				}
				echo '</div>';
			}

			if ($tag_fansfriends) {
				echo '<div class="zikiplugin-fansfriends" style="text-align:center; padding-top:10px">';
				if ($stats_fans) {
					echo '<a href="'.$url_ziki.'/favorites/fans">';
					echo $stats_fans.' fans ';	
					echo '</a>';	
				}
				if ($stats_fans) {
					if ($stats_fans) {
						echo ' / ';
					}
					echo '<a href="'.$url_ziki.'/favorites/friends">';
					echo $stats_friends.' friends ';	
					echo '</a>';	
				}
				echo '</div>';
			}			

			if ($tag_twitter && (strcmp($twitter_mail, "")) && (strcmp($twitter_password, ""))) {
				echo '<div class="zikiplugin-twitter" style="text-align:center; padding-top:10px">';
				$img_url = ZIKIFAVICONURL."twitter.com.ico";
     			echo '<img src="'.$img_url.'" alt="Twitter"> '.$twitter_text;
				echo '</div>';
			}

			if ($tag_stats) {
				echo '<div class="zikiplugin-stats" style="text-align:center; padding-top:10px">';
				if ($stats_feedburner_circulation) {
					echo '<a href="'.get_bloginfo('rss2_url').'">';
					echo '<img src="'.ZIKIFAVICONURL.'feedburner.com.ico"> '.$stats_feedburner_circulation.' readers ';	
					echo '</a>';	
				}
				if ($stats_technorati_rank) {
					echo '<a href="http://technorati.com/faves?sub=addfavbtn&add='.get_bloginfo('url').'">';
					echo '<img src="'.ZIKIFAVICONURL.'technorati.com.ico"> rank '.$stats_technorati_rank;
					echo '</a>';	
				}		
				echo '</div>';
			}			
	
			echo '</div>';
			// End of the Zikiplugin HTML code			
			
			// echo widget closing tag
			echo $after_widget;
	}


	// Settings form
	function widget_ZikiPlugin_control() {

		// Get options
		$options = get_option('widget_ZikiPlugin');
		
		// options exist? if not set defaults
		if ( !is_array($options) )
			$options = array('title'=>'My Ziki', 'name'=>'', 
							 'tag_realname'=>'', 'tag_baseline'=>'', 'tag_profile'=>'', 
							 'tag_fansfriends'=>'', 'tag_stats'=>'', 
							 'tag_twitter'=>'', 'twitter_mail'=>'', 'twitter_password'=>'');
		
		// form posted?
		if ( $_POST['ZikiPlugin-submit'] ) {
			// Remember to sanitize and format use input appropriately.
			$options['title'] = strip_tags(stripslashes($_POST['ZikiPlugin-title']));
			$options['name'] = strip_tags(stripslashes($_POST['ZikiPlugin-name']));
			$options['tag_realname'] = strip_tags(stripslashes($_POST['ZikiPlugin-tag-realname']));
			$options['tag_baseline'] = strip_tags(stripslashes($_POST['ZikiPlugin-tag-baseline']));
			$options['tag_profile'] = strip_tags(stripslashes($_POST['ZikiPlugin-tag-profile']));
			$options['tag_fansfriends'] = strip_tags(stripslashes($_POST['ZikiPlugin-tag-fansfriends']));			
			$options['tag_stats'] = strip_tags(stripslashes($_POST['ZikiPlugin-tag-stats']));
			$options['tag_twitter'] = strip_tags(stripslashes($_POST['ZikiPlugin-tag-twitter']));
			$options['twitter_mail'] = strip_tags(stripslashes($_POST['ZikiPlugin-twitter-mail']));
			$options['twitter_password'] = strip_tags(stripslashes($_POST['ZikiPlugin-twitter-password']));
			update_option('widget_ZikiPlugin', $options);
		}

		// Get options for form fields to show
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$name = htmlspecialchars($options['name'], ENT_QUOTES);
		$tag_realname = $options['tag_realname'];
		$tag_baseline = $options['tag_baseline'];
		$tag_profile = $options['tag_profile'];
		$tag_fansfriends = $options['tag_fansfriends'];
		$tag_stats = $options['tag_stats'];
		$tag_twitter = $options['tag_twitter'];
		$twitter_mail = $options['twitter_mail'];
		$twitter_password = $options['twitter_password'];
		
		// The form fields
		// Title
		echo '<p style="text-align:right;">
				<label for="ZikiPlugin-title">' . __('Title:') . ' 
				<input style="width: 200px;" id="ZikiPlugin-title" name="ZikiPlugin-title" type="text" value="'.$title.'" />
				</label></p>';
		// Name (nickname to use for the widget)
		echo '<p style="text-align:right;">
				<label for="ZikiPlugin-name">' . __('Name:') . ' 
				<input style="width: 200px;" id="ZikiPlugin-name" name="ZikiPlugin-name" type="text" value="'.$name.'" />
				</label></p>';
		// Tag display real name (first and last)				
		echo '<p style="text-align:right;">
				<label for="ZikiPlugin-tag-realname">' . __('Display first and last name ?') . ' 
				<input type="checkbox" name="ZikiPlugin-tag-realname"';
		if ($tag_realname) echo ' CHECKED ';
		echo '</label></p>';
		// Tag baseline				
		echo '<p style="text-align:right;">
				<label for="ZikiPlugin-tag-baseline">' . __('Display the baseline ?') . ' 
				<input type="checkbox" name="ZikiPlugin-tag-baseline"';
		if ($tag_baseline) echo ' CHECKED ';
		echo '</label></p>';		
		// Tag display profile		
		echo '<p style="text-align:right;">
				<label for="ZikiPlugin-tag-profile">' . __('Display profile links (Facebook, Twister...) ?') . ' 
				<input type="checkbox" name="ZikiPlugin-tag-profile"';
		if ($tag_profile) echo ' CHECKED ';		
		echo '</label></p>';
		// Tag display stats fans and friends		
		echo '<p style="text-align:right;">
				<label for="ZikiPlugin-tag-fansfriends">' . __('Display number of fans & friends ?') . ' 
				<input type="checkbox" name="ZikiPlugin-tag-fansfriends"';
		if ($tag_fansfriends) echo ' CHECKED ';		
		echo '</label></p>';		
		// Tag display stats		
		echo '<p style="text-align:right;">
				<label for="ZikiPlugin-tag-stats">' . __('Display statistics (Feeburner, Technorati...) ?') . ' 
				<input type="checkbox" name="ZikiPlugin-tag-stats"';
		if ($tag_stats) echo ' CHECKED ';		
		echo '</label></p>';
		// Twitter
		echo '<p style="text-align:right;">
				<label for="ZikiPlugin-tag-twitter">' . __('Display last Twitter baseline ?') . ' 
				<input type="checkbox" name="ZikiPlugin-tag-twitter"';
		if ($tag_twitter) echo ' CHECKED ';
		echo '</label></p>';
		echo '<p style="text-align:right;">
				<label for="ZikiPlugin-twitter-mail">' . __('Twitter mail:') . ' 
				<br/><input style="width: 200px;" id="ZikiPlugin-twitter-mail" name="ZikiPlugin-twitter-mail" type="text" value="'.$twitter_mail.'" />
				</label></p>';
		echo '<p style="text-align:right;">
				<label for="ZikiPlugin-twitter-password">' . __('Twitter password:') . ' 
				<br/><input style="width: 200px;" id="ZikiPlugin-twitter-password" name="ZikiPlugin-twitter-password" type="password" value="'.$twitter_password.'" />
				</label></p>';			
		// Submit		
		echo '<input type="hidden" id="ZikiPlugin-submit" name="ZikiPlugin-submit" value="1" />';
	}
	
	// Register widget for use
	register_sidebar_widget(array('ZikiPlugin', 'widgets'), 'widget_ZikiPlugin');

	// Register settings for use, 300x100 pixel form
	register_widget_control(array('ZikiPlugin', 'widgets'), 'widget_ZikiPlugin_control', 300, 200);
}

// Run code and init
add_action('widgets_init', 'widget_ZikiPlugin_init');

?>