<?php

ini_set('max_execution_time', 3600);
register_shutdown_function('shutdown');

function shutdown() 
{
	if (connection_status() == CONNECTION_TIMEOUT) 
	{
		fwrite($this->logfile,'['.date('Y-m-d H:i:s').']|Reuters Direct - Script Timeout'."\r\n");
	}
}

if ( ! defined( 'ABSPATH' ) ) exit;

require_once(ABSPATH . '/wp-admin/includes/post.php');
require_once(ABSPATH . '/wp-admin/includes/taxonomy.php');
require_once(ABSPATH . '/wp-admin/includes/import.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
global $logfile;

class Reuters_Direct {

	private static $_instance = null;
	public $settings = null;
	public $_version;
	public $_token;
	public $file;
	public $dir;
	public $logfile;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file = '', $version = '2.4.1' ) {
		$this->_version = $version;
		$this->_token = 'Reuters_Direct';

		$this->file = $file;
		$this->dir = dirname( $this->file );

		// Creating log file
		$log = WP_PLUGIN_DIR."/reuters-direct/log.txt";
		$logfile = fopen($log, "a") or die("Unable to open file!");
		$this->logfile = $logfile;

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// SETTING UP CRON JOBS
		register_activation_hook( $this->file, array( $this, 'activate' ) );
		register_deactivation_hook( $this->file, array( $this, 'deactivate' ) );
	
		add_filter('cron_schedules', array($this,'custom_schedules'));
		add_action( 'rd_cron', array($this, 'import'));		
	}

	/**
	 * Main Reuters_Direct Instance
	 *
	 * Ensures only one instance of Reuters_Direct is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Reuters_Direct()
	 * @return Main Reuters_Direct instance
	 */
	public static function instance ( $file = '', $version = '2.4.1' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	}

	/**
	 * Adding custom schedule
	 * @return array schedules array
	 */
	public function custom_schedules($schedules) {
    	$schedules['every5min'] = array('interval' => 5*60, 'display' => 'Every five minutes');
    	return $schedules;
	}

	public function activate() {
		// Creating upload directory
		$upload_dir = wp_upload_dir();
  		$upload_loc = $upload_dir['basedir']."/Reuters_Direct_Media";
  		if (!is_dir($upload_loc)) {
    		fwrite($this->logfile,'['.date('Y-m-d H:i:s').']|Creating local directory for media download'."\r\n");
    		wp_mkdir_p($upload_loc);
    	}

		// Adding cron job
		if (!wp_next_scheduled('rd_cron')) {
			wp_schedule_event( time(), 'every5min', 'rd_cron' );
		}

	}

	public function deactivate() {
		// Removing cron job
		wp_clear_scheduled_hook('rd_cron');
		// Deleteing options
		delete_option('rd_username_field');
		delete_option('rd_password_field');
		delete_option('rd_status_radiobuttons');
		delete_option('rd_channel_checkboxes');
		delete_option('rd_category_checkboxes');
		// Closing log file
		$log = WP_PLUGIN_DIR."/reuters-direct/log.txt";
		$logfile = fopen($log, "w") or die("Unable to open file!");
		fclose($logfile);
	}

	// FUNCTION TO PULL CONTENT
	public function import() {
		if(!get_option('rd_username_field')){
			fwrite($this->logfile,'['.date('Y-m-d H:i:s').']|User not logged in'."\r\n");
			return;
		}
		$this->resetLog();
		fwrite($this->logfile,'['.date('Y-m-d H:i:s').']|Cron job started'."\r\n");
		$user_token = $this->getToken();
		if($user_token!="")
		{
			if(!get_option('rd_channel_checkboxes')){
				fwrite($this->logfile,'['.date('Y-m-d H:i:s').']|No channels selected'."\r\n");
			}
			else{
				$this->getPosts($user_token);
			}	
		}
		fwrite($this->logfile,'['.date('Y-m-d H:i:s').']|Cron job complete<br><br>'."\r\n");
	}

	// FUNCTION TO GET TOKEN
	public function getToken()
	{
		$username = get_option('rd_username_field');
		$password = get_option('rd_password_field');
	  	$token_url = "https://cache.commerce.reuters.com/rmd/rest/xml/login?username=".$username."&password=".$password;
	  	$ch = curl_init();
	  	curl_setopt($ch, CURLOPT_URL, $token_url);
	  	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	  	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	  	curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
	  	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	  	$token = simplexml_load_string(curl_exec($ch));
	  	curl_close($ch);
	  	if(empty($token)){
	  		fwrite($this->logfile,'['.date('Y-m-d H:i:s').']|Failed to retrieve token'."\r\n");
	  	}
	  	return $token;
	}

	// FUNCTION TO RESET LOG FILE
	public function resetLog()
	{
		$log = WP_PLUGIN_DIR."/reuters-direct/log.txt";
		if(filesize($log)>5000000)
		{
			// Closing log file
			$logfile = fopen($log, "w") or die("Unable to open file!");
			fclose($logfile);
		}
	}

	// FUNCTION TO GET XML
	public function getXml($content_url)
	{
		$content_curl = curl_init();
  		curl_setopt($content_curl, CURLOPT_URL, $content_url);
  		curl_setopt($content_curl, CURLOPT_RETURNTRANSFER, true);
		$content_xml = simplexml_load_string(curl_exec($content_curl));
  		curl_close($content_curl);
  		return $content_xml;
	}

	// FUNCTION TO CREATE DIRECTORY
	public function createDirectory($channel_name)
	{
		$upload_dir = wp_upload_dir();
		$upload_loc = $upload_dir['basedir']."/Reuters_Direct_Media/".$channel_name;
  		if (!is_dir($upload_loc)) {
    		fwrite($this->logfile,'['.date('Y-m-d H:i:s').']|Creating local directory for '.$channel_name."\r\n");
    		wp_mkdir_p($upload_loc);
    	}
    }

    // FUNCTION TO CHECK IF STORY ID ALREADY EXISTS
	public function storyIdExists($story_id) 
	{
		global $wpdb;
		$query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='story_id'";
		$args = array();
	 
		if ( !empty ( $story_id ) ) {
		     $query .= " AND meta_value LIKE '%s' ";
		     $args[] = $story_id;
		}
	 
		if ( !empty ( $args ) )
		     return $wpdb->get_var( $wpdb->prepare($query, $args) );
	 
		return 0;
	}    

	// FUNCTION TO HANDLE DIFFERNT CHANNEL TYPES
	public function getPosts($user_token) 
	{
		global $wpdb;
		$index = 0;
		$saved_channels = get_option('rd_channel_checkboxes');		
		$saved_categories = get_option('rd_category_checkboxes');
		$post_status = get_option('rd_status_radiobuttons');
		$saved_rendition = get_option('rd_image_radiobuttons');
		$image_post = false;
		if($post_status == 'publish images')
		{
			$post_status = 'publish';
			$image_post = true;
		}
		foreach( $saved_channels as $channel => $alias ) 
		{
			$channel_data = explode(':', $alias);
			$channel_alias = $channel_data[0];
			$channel_type = $channel_data[1];
			$channel_name = $channel_data[2];

			if($channel_type == 'OLR')
			{
				$this->createDirectory($channel_name);
				$content_url = 'http://rmb.reuters.com/rmd/rest/xml/packages?channel='.$channel_alias.'&limit=5&token='.$user_token;
				$content_xml = $this->getXml($content_url);
				$this->getOLR($content_xml, $channel_alias, $user_token, $channel_name, $saved_categories, $saved_rendition, $post_status, $image_post);
			}
			else if($channel_type == 'TXT')
			{
				$content_url = 'http://rmb.reuters.com/rmd/rest/xml/items?channel='.$channel_alias.'&limit=5&token='.$user_token;
				$content_xml = $this->getXml($content_url);
				$this->getTexts($content_xml, $channel_alias, $user_token, $channel_name, $saved_categories);
			}
			else if(($channel_type == 'PIC')||($channel_type == 'GRA'))
			{
				$this->createDirectory($channel_name);
				$content_url = 'http://rmb.reuters.com/rmd/rest/xml/items?channel='.$channel_alias.'&limit=5&token='.$user_token;
				$content_xml = $this->getXml($content_url);
				$this->getImages($content_xml, $channel_alias, $channel_name, $user_token, $channel_type);
			}	
		}
	}

	// FUNCTION TO GET OLR
	public function getOLR($content_xml, $channel_alias, $user_token, $channel_name, $saved_categories,$saved_rendition, $post_status, $image_post)
	{
		$newpost = 0;
		$oldpost = 0;
		foreach ($content_xml->result as $item) 
		{
			$story_id = sanitize_title((string) $item->guid);
			$pubDate = (string) $item->dateCreated;	
			$post_date_unix = strtotime($pubDate);

			// Handling existing story
			if ($post_id = $this->storyIdExists($story_id))
			{
				$latest_timestamp = get_post_meta( $post_id, 'unix_timestamp', true ); 
				if($post_date_unix > $latest_timestamp)
				{
					// Updating the post contents
					$post = $this->getOLRArray($item, $post_date_unix, $channel_alias, $user_token, $saved_categories, $saved_rendition, $post_status);
					$image_content = $post['image_content'];
					$post['ID'] = $post_id ;

					// Update Post with Images
					if(count($image_content)>=1)
					{
						$image_tag = $this->addImages($post_id, $image_content, $channel_name);
						$post['post_content'] = $post['post_content'].$image_tag;
						wp_update_post($post);
						wp_set_post_tags( $post_id, '', false );
					}
					// Update Post without Images
					else if (count($image_content)==0 && $image_post)
					{
						$post['post_status'] = 'draft';
						wp_update_post($post);
					}
					else
					{
						wp_update_post($post);
					}		
					update_post_meta($post_id, 'unix_timestamp', $post_date_unix);
					$oldpost++;
				}
				

			}
			// Handling new story
			else
			{
				//Getting post content
				$post = $this->getOLRArray($item, $post_date_unix, $channel_alias, $user_token, $saved_categories, $saved_rendition, $post_status);
				$categories = $post['categories'];
				$image_content = $post['image_content'];

				// Posting the post contents
				$post_id = wp_insert_post($post);
				if ( is_wp_error( $post_id ) )
					return $post_id;
				if (!$post_id)
					return;

				// Post with Images
				if(count($image_content)>=1)
				{
					$image_tag = $this->addImages($post_id, $image_content, $channel_name);
					$post['post_content'] = $post['post_content'].$image_tag;
					$post['ID'] = $post_id ;
					wp_update_post($post);
				}
				//Post without Images
				else if (count($image_content)==0 && $image_post)
				{
					$post['ID'] = $post_id ;
					$post['post_status'] = 'draft';
					wp_update_post($post);
					wp_set_post_tags( $post_id, 'Reuters OLR - no image', false );
				}

				if (0 != count($categories))
					wp_create_categories($categories, $post_id);

				add_post_meta($post_id, 'story_id', $story_id);
				add_post_meta($post_id, 'unix_timestamp', $post_date_unix);
				$newpost++;
			}
		}
		fwrite($this->logfile,'['.date('Y-m-d H:i:s').']|Reuters OLR: '.$channel_name.'<br><span style="color: #0074a2;">'.$newpost.' New & '.$oldpost.' Updated</span>' ."\r\n");	
    }

   // FUNCTION TO GET OLR ARRAY
    public function getOLRArray($item, $post_date_unix, $channel_alias, $user_token, $saved_categories, $saved_rendition, $post_status)
    {
		$post_title = (string) $item->headline;
    	$post_name = implode(' ', array_slice(explode(' ', $post_title), 0, 4));
		$post_date_gmt = gmdate('Y-m-d H:i:s', $post_date_unix);
		$post_date = get_date_from_gmt( $post_date_gmt );
		$post_author = 1;
		$categories = array();
		$image_content = array();
		$text_content="";

		foreach($item->mainLinks->link as $links)
		{
			$mediaType = (string) $links->mediaType;
			$id = (string) $links->id;
			$item_url = 'http://rmb.reuters.com/rmd/rest/xml/item?id='.$id.'&channel='.$channel_alias.'&token='.$user_token;
			$item_xml = $this->getXml($item_url);

			if($mediaType == "T")
			{
				$text_content = $item_xml->itemSet->newsItem->contentSet->inlineXML->html->body->asXML();
				// Getting the categories
				foreach($item_xml->itemSet->newsItem->contentMeta->subject as $subject)
				{
					$category_code = (string) $subject->attributes()->qcode;
					list($type, $code) = explode(':', $category_code);
					if( in_array( $type, $saved_categories ) )
					{ 
						array_push($categories, $category_code);
					}
				}
				if(in_array('Agency_Labels', $saved_categories))
				{
					foreach($item_xml->itemSet->newsItem->itemMeta->memberOf as $memberOf)
					{
						$category_code = (string) $memberOf->name;
						array_push($categories, $category_code);
					}
				}
				if(in_array('Custom_Category', $saved_categories))
				{
					$custom_category = get_option('rd_custom_category');
					array_push($categories, $custom_category);
				}
			}
			else if($mediaType == "P")
			{
				$image_detail = array();
				$image_detail['headline'] = (string) $item_xml->itemSet->newsItem->contentMeta->headline;
				$image_detail['description'] = (string) $item_xml->itemSet->newsItem->contentMeta->description;

				foreach($item_xml->itemSet->newsItem->contentSet->remoteContent as $remoteContent)
				{
					$image_type = (string) $remoteContent->attributes()->rendition;
					if($image_type == $saved_rendition)
					{
						$image_ref = (string) $remoteContent->attributes()->href;
						$image_detail['url'] = $image_ref.'?token='.$user_token;
						$image_content[$image_ref] = $image_detail;
					}
				}
			}
		}
		$post_content = $text_content;
		$post = compact('post_name', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_status', 'categories', 'image_content');
    	return $post;
    }

    // FUNCITON TO GET OLR IMAGES
	public function addImages($post_id, $image_content, $channel_name) {
		$upload_dir = wp_upload_dir();
		$image_count = 0;
		$image_tag = '';
		foreach($image_content as $image_ref => $image_detail)
		{
			$image_url = $image_detail['url'];
			$headline = $image_detail['headline'];
			$description = $image_detail['description'];
			$image_curl = curl_init();
		  	curl_setopt($image_curl, CURLOPT_URL, $image_url);
		  	curl_setopt($image_curl, CURLOPT_RETURNTRANSFER, true);
			$image_data = curl_exec($image_curl);
		  	curl_close($image_curl);
		  	// Saving the images
			$basename = basename($image_ref);
			$filename = sanitize_file_name($basename);
			$file = $upload_dir['basedir']."/Reuters_Direct_Media/" . $channel_name ."/". $filename . '.jpg';
			file_put_contents($file, $image_data);
			// Makeing a post entry
			$attachment = array(    
			    'post_mime_type' => 'image/jpg',
			    'post_author' => 1,
			    'post_title' => implode(' ', array_slice(explode(' ', $headline), 0, 10)),
			    'post_content' => $description,
			    'post_excerpt' => $headline,
			    'guid' => $upload_dir['basedir']."/Reuters_Direct_Media/" . $channel_name ."/". $filename . '.jpg',
			    'post_status' => 'inherit'
			);
			// Attaching Images to Post
			$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			// Handling Multiple Images
			$url = wp_get_attachment_url( $attach_id );
			$attach_link = get_attachment_link( $attach_id );
			$image_tag .= '<p><a href="'.$attach_link.'"><img src="'.$url.'" alt="'.$filename.'"></a></p>';
			// Setting Featured Image
			if($image_count == 0)
				set_post_thumbnail( $post_id, $attach_id );	
			$image_count++;
		}
		return $image_tag;
	}


    // FUNCTION TO GET PIC
	public function getImages($content_xml, $channel_alias, $channel_name, $user_token, $channel_type)
	{
		$newpost = 0;
		$upload_dir = wp_upload_dir();
		$saved_rendition = get_option('rd_image_radiobuttons');
		// GRA format correction
		if(($saved_rendition =="rend:baseImage")&&($channel_type =="GRA")){
			$saved_rendition = "rend:viewImage";
		}
		
		foreach ($content_xml->result as $item) 
		{
			$story_id = sanitize_title((string) $item->guid);
			if (!$this->storyIdExists($story_id))
			{
				$id = (string) $item->id;
				$item_url = 'http://rmb.reuters.com/rmd/rest/xml/item?id='.$id.'&channel='.$channel_alias.'&token='.$user_token;
				$item_xml = $this->getXml($item_url);

				$headline = (string) $item_xml->itemSet->newsItem->contentMeta->headline;
				$description = (string) $item_xml->itemSet->newsItem->contentMeta->description;

				foreach($item_xml->itemSet->newsItem->contentSet->remoteContent as $remoteContent)
				{
					$image_type = (string) $remoteContent->attributes()->rendition;
					if($image_type == $saved_rendition)
					{
						$image_ref = (string) $remoteContent->attributes()->href;
						$image_url = $image_ref.'?token='.$user_token;
						$image_curl = curl_init();
					  	curl_setopt($image_curl, CURLOPT_URL, $image_url);
					  	curl_setopt($image_curl, CURLOPT_RETURNTRANSFER, true);
						$image_data = curl_exec($image_curl);
					  	curl_close($image_curl);
					  	// Saving the images
						$basename = basename($image_ref);
						$filename = sanitize_file_name($basename);
						$file = $upload_dir['basedir']."/Reuters_Direct_Media/" . $channel_name ."/". $filename . '.jpg';
						file_put_contents($file, $image_data);
						// Making a post entry
						$attachment = array(
					    'post_mime_type' => 'image/jpg',
					    'post_author' => 1,
					    'post_title' => implode(' ', array_slice(explode(' ', $headline), 0, 10)),
					    'post_content' => $description,
					    'post_excerpt' => $headline,
					    'guid' => $upload_dir['basedir']."/Reuters_Direct_Media/" . $channel_name ."/". $filename . '.jpg',
					    'post_status' => 'inherit'
						);
						$attach_id = wp_insert_attachment( $attachment, $file);
						$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
						wp_update_attachment_metadata( $attach_id, $attach_data );
						add_post_meta($attach_id, 'story_id', $story_id);
						$newpost++;
					}
				}
			}
		}
		fwrite($this->logfile,'['.date('Y-m-d H:i:s').']|Reuters '.$channel_type.': '.$channel_name.'<br><span style="color: #0074a2;">'.$newpost.' New</span>' ."\r\n");		
    }

    // FUNCTION TO GET TXT
	public function getTexts($content_xml, $channel_alias, $user_token, $channel_name, $saved_categories)
	{
		$newpost = 0;
		$oldpost = 0;
		foreach ($content_xml->result as $item) 
		{
			$story_id = sanitize_title((string) $item->guid);
			$pubDate = (string) $item->dateCreated;	
			$post_date_unix = strtotime($pubDate);
			// Handling existing story
			if ($post_id = $this->storyIdExists($story_id))
			{
				$latest_timestamp = get_post_meta( $post_id, 'unix_timestamp', true ); 
				if($post_date_unix > $latest_timestamp)
				{
					// Updating the post contents
					$post = $this->getPostArray($item, $post_date_unix, $channel_alias, $user_token, $saved_categories);
					$post['ID'] = $post_id ;
					wp_update_post($post);
					update_post_meta($post_id, 'unix_timestamp', $post_date_unix);
					$oldpost++;
				}
			}
			// Handling new story
			else
			{
				// Posting the post contents
				$post = $this->getPostArray($item, $post_date_unix, $channel_alias, $user_token, $saved_categories);
				$categories = $post['categories'];
				$post_id = wp_insert_post($post);
				if ( is_wp_error( $post_id ) )
					return $post_id;
				if (!$post_id)
					return;
				if (0 != count($categories))
					wp_create_categories($categories, $post_id);

				add_post_meta($post_id, 'story_id', $story_id);
				add_post_meta($post_id, 'unix_timestamp', $post_date_unix);
				wp_set_post_tags( $post_id, 'Reuters TXT', false );
				$newpost++;
			}
		}
		fwrite($this->logfile,'['.date('Y-m-d H:i:s').']|Reuters TXT: '.$channel_name.'<br><span style="color: #0074a2;">'.$newpost.' New & '.$oldpost.' Updated</span>' ."\r\n");		
    }


    // FUNCTION TO GET TXT ARRAY
    public function getPostArray($item, $post_date_unix, $channel_alias, $user_token, $saved_categories)
    {
    	$post_title = (string) $item->headline;
    	$post_name = implode(' ', array_slice(explode(' ', $post_title), 0, 4));
		$post_date_gmt = gmdate('Y-m-d H:i:s', $post_date_unix);
		$post_date = get_date_from_gmt( $post_date_gmt );
		$post_author = 1; 
		$post_status = 'draft';
		$categories = array();
		// Getting the text contents
		$id = (string) $item->id;
		$item_url = 'http://rmb.reuters.com/rmd/rest/xml/item?id='.$id.'&channel='.$channel_alias.'&token='.$user_token;
		$item_xml = $this->getXml($item_url);
		$post_content = $item_xml->itemSet->newsItem->contentSet->inlineXML->html->body->asXML();
		// Getting the categories
		foreach($item_xml->itemSet->newsItem->contentMeta->subject as $subject)
		{
			$category_code = (string) $subject->attributes()->qcode;
			list($type, $code) = explode(':', $category_code);
			if( in_array( $type, $saved_categories ) )
			{ 
				array_push($categories, $category_code);
			}
		}
		if(in_array('Agency_Labels', $saved_categories))
		{
			foreach($item_xml->itemSet->newsItem->itemMeta->memberOf as $memberOf)
			{
				$category_code = (string) $memberOf->name;
				array_push($categories, $category_code);
			}
		}
		if(in_array('Custom_Category', $saved_categories))
		{
			$custom_category = get_option('rd_custom_category');
			array_push($categories, $custom_category);
		}
		// Forming the post array
		$post = compact('post_name', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_status', 'categories');
    	return $post;
    }
}