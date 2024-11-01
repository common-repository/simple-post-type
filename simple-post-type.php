<?php
/**
 * Plugin Name: Simple Post Type
 * Plugin URI: http://www.madadim.co.il
 * Description: Create custom post type
 * Version: 1.2.1
 * Author: Yehi Co
 * Author URI: http://www.madadim.co.il
 * License: GPL2
 * Text Domain: simple-post-type


Simple Post Type is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Simple Post Type is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Simple Post Type. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/

define( 'SPT__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPT__PLUGIN_URL', plugin_basename(__FILE__) );

global $spt_version;
$spt_version = '1.2.1'; // version changed from 1.0 to 1.0

function simple_post_type_install() {
    global $wpdb;
    global $spt_version;

    $table_name = $wpdb->prefix . 'simple_post_type'; // do not forget about tables prefix
    $sql = "CREATE TABLE " . $table_name . " (
      id int(11) NOT NULL AUTO_INCREMENT,
      post_ID int(11) NOT NULL,
      name VARCHAR(100)	 NOT NULL,
      menu_name VARCHAR(100) NOT NULL,
      menu_icon VARCHAR(200) NOT NULL,
      description longtext NOT NULL,
      taxonomies VARCHAR(100) NOT NULL,
      PRIMARY KEY  (id)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('spt_version', $spt_version);

    $installed_ver = get_option('spt_version');
    if ($installed_ver != $spt_version) {
        $sql = "CREATE TABLE " . $table_name . " (
          id int(11) NOT NULL AUTO_INCREMENT,
          post_ID int(11) NOT NULL,
          name VARCHAR(100) NOT NULL,
          menu_name VARCHAR(100) NOT NULL,
          menu_icon VARCHAR(200) NOT NULL,
          description longtext NOT NULL,
          taxonomies VARCHAR(100) NOT NULL,
          PRIMARY KEY  (id)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('spt_version', $spt_version);
    }
    
}

register_activation_hook(__FILE__, 'simple_post_type_install');

function spt_update_db_check() {
    global $spt_version;
    if (get_site_option('spt_version') != $spt_version) {
        simple_post_type_install();
    }
}

add_action('plugins_loaded', 'spt_update_db_check');

function spt_columns($columns) {
	
	unset(
		$columns['date'],
		$columns['comments']
	);
	$new_columns = array(
		'description' => __('Description', 'simple-post-type'),
		'taxonomies' => __('Taxonomies', 'simple-post-type'),
	);
    return array_merge($columns, $new_columns);
}
add_filter('manage_spt_posts_columns' , 'spt_columns');


 
add_action('manage_pages_custom_column' , 'book_custom_columns', 10, 2 );
 
function book_custom_columns( $column, $post_id ) {
    global $wpdb;
	$post = get_post();
	$table_name = $wpdb->prefix . 'simple_post_type';
	$post_type_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE post_ID = $post->ID") );
    switch ( $column ) {

    case 'description' :
	$post_type_description = $post_type_item->description;
	
        echo $post_type_description;
        break;

    case 'taxonomies' :
	$post_type_taxonomies = $post_type_item->taxonomies;
	
        echo $post_type_taxonomies;
        break;
    }
}


/*-------------------class start------------------*/

class YCspt {

function __construct() {

	add_action( 'init', array( $this, 'register_spt' ) );
	add_action( 'admin_menu', array($this, 'spt_admin_menu') );
	add_action( 'add_meta_boxes', array($this, 'spt_create_meta_boxes') );
	add_action( 'plugins_loaded', array($this, 'spt_load_textdomain') );
	add_action( 'save_post', array($this, 'save_post_type_values') );
	add_action( 'before_delete_post', array($this, 'delete_post_row') );
	add_action( 'init', array( $this, 'register_custom_post_type' ) );
	// filter
	add_filter( 'post_updated_messages', array($this, 'spt_update_messages') );

}
    
function register_spt() {
    register_post_type( 'spt', array(
        'labels' => array(
		'name'               => __( 'Post Types', 'simple-post-type' ),
		'singular_name'      => _x( 'Post Type', 'post type singular name', 'simple-post-type' ),
		'menu_name'          => _x( 'Post Types', 'admin menu', 'simple-post-type' ),
		'name_admin_bar'     => _x( 'Post Type', 'add new on admin bar', 'simple-post-type' ),
		'add_new'            => _x( 'Add New', 'Post Type', 'simple-post-type' ),
		'add_new_item'       => __( 'Add New', 'simple-post-type' ),
		'new_item'           => __( 'New Custom Post Type', 'simple-post-type' ),
		'edit_item'          => __( 'Edit Custom Post Type', 'simple-post-type' ),
		'view_item'          => __( 'View Custom Post Type', 'simple-post-type' ),
		'all_items'          => __( 'Post Types', 'simple-post-type' ),
		'search_items'       => __( 'Search Custom Post Type', 'simple-post-type' ),
		'parent_item_colon'  => __( 'Parent Custom Post Type:', 'simple-post-type' ),
		'not_found'          => __( 'No Custom Post Type found.', 'simple-post-type' ),
		'not_found_in_trash' => __( 'No Custom Post Type found in Trash.', 'simple-post-type' ),
		),
		
		// Frontend // Admin
		'supports'              => array( 'title', ),
		'hierarchical'          => true,
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => false,
		'menu_position'         => 100,
		'menu_icon'             => 'dashicons-welcome-add-page',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => false,		
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
    ) );    
}

function spt_item_meta_box() {
      
	global $wpdb;
	$post = get_post();
	$table_name = $wpdb->prefix . 'simple_post_type';
	$post_type_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE post_ID = $post->ID") );
	
	$post_type_menu_name = $post_type_item->menu_name;
	$post_id = $post_type_item->post_ID;

?>
	<form id="formspt" method="POST">
	<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
	    <input type="hidden" name="prevent_delete_meta_movetotrash" id="prevent_delete_meta_movetotrash" value="<?php echo wp_create_nonce(SPT__PLUGIN_URL.$post->ID); ?>" />
	    <tbody>
	    <tr class="form-field">
	        <th valign="top" scope="row">
	            <label for="name"><?php _e('Post Type Name URL', 'simple-post-type')?></label>
	            <p><?php _e('Use only English letters and no spaces', 'simple-post-type')?></p>
	        </th>
	        <?php if ($post_type_item->menu_name == '') { ?>
	        <td>
	            <input id="menu_name" name="menu_name" type="text" style="width: 95%" value="<?php echo esc_attr($post_type_item->menu_name)?>"
	                   size="50" class="" placeholder="<?php _e('Post Type Name URL', 'simple-post-type')?>">
	        </td>
	        <?php } else { ?>
	        <td>
	            <input id="menu_name" name="menu_name" type="text" style="width: 95%" value="<?php echo esc_attr($post_type_item->menu_name)?>"
	                   size="50" class="" placeholder="<?php _e('Post Type Name URL', 'simple-post-type')?>" disabled>
	        </td>
	        <?php } ?>
	    </tr>
	    <tr class="form-field">
	        <th valign="top" scope="row">
	            <label for="menu_icon"><?php _e('Menu Icon', 'simple-post-type')?></label>
	            <p><?php _e('Please enter the name of the icon from the icons in the link', 'simple-post-type')?><br><a href="https://developer.wordpress.org/resource/dashicons" target="_blank"><?php _e('The link', 'simple-post-type')?></a></p>
	        </th>
	        <td>
	            <input id="menu_icon" name="menu_icon" type="text" style="width: 95%" value="<?php echo esc_attr($post_type_item->menu_icon)?>"
	                   size="50" class="" placeholder="dashicons-admin-post">
	        </td>
	    </tr>
	    <tr class="form-field">
	        <th valign="top" scope="row">
	            <label for="description"><?php _e('Description', 'simple-post-type')?></label>
	            <p><?php _e('Short description of the purpose of the group that posts', 'simple-post-type')?></p>
	        </th>
	        <td>
	            <textarea rows="4" cols="50" name="description"><?php echo esc_attr($post_type_item->description)?></textarea>
	        </td>
	    </tr>
	    <tr class="form-field">
	        <th valign="top" scope="row">
	            <label for="taxonomies"><?php _e('Taxonomies', 'simple-post-type')?></label>
	            <p><?php _e('You can add the core Taxonomies into posts', 'simple-post-type')?></p>
	        </th>
	        <td>
	        <?php
                $checkbox_value = $post_type_item->taxonomies;
                $check_category = strpos($checkbox_value, 'category');
                $check_post_tag = strpos($checkbox_value, 'post_tag');

                if ($check_category === false)
                {
                    ?>
                        <br><input name="taxonomies_category" type="checkbox" value="category"><label for="categories"><?php _e('Categories', 'simple-post-type')?></label><br>
                    <?php
                } else {
                    ?>  
                        <input name="taxonomies_category" type="checkbox" value="category" checked><label for="categories"><?php _e('Categories', 'simple-post-type')?></label><br>
                    <?php
                }
                
                if ($check_post_tag === false)
                {
                    ?>
                        <input name="taxonomies_post_tag" type="checkbox" value="post_tag"><label for="post_tag"><?php _e('Post tags', 'simple-post-type')?></label>
                    <?php
                } else {
                    ?>  
                        <input name="taxonomies_post_tag" type="checkbox" value="post_tag" checked><label for="post_tag"><?php _e('Post tags', 'simple-post-type') ?></label>
                    <?php
                }
            ?>
	        
	        </td>
	    </tr>
	    </tbody>
	</table>
	</form>
	
<?php

}

function save_post_type_values() {

    if( get_post_type() == 'spt' ) {
	global $wpdb;
	$post = get_post();
	$table_name = $wpdb->prefix . 'simple_post_type'; // do not forget about tables prefix
	$post_type_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE post_ID = $post->ID") );

	$post_id = $post_type_item->post_ID;
	$taxonomies_category = $_POST['taxonomies_category'];
	$taxonomies_post_tag = $_POST['taxonomies_post_tag'];
	$post_type_taxonomies = $taxonomies_category . ' ,  ' . $taxonomies_post_tag;
	
	if (!wp_verify_nonce($_POST['prevent_delete_meta_movetotrash'], SPT__PLUGIN_URL.$post->ID)) { return $post_id; }
	
	if ( $post_type_item->post_ID == $post->ID ) {
		$post_type_name = $_POST['post_title'];
		$post_type_description = $_POST['description'];
		$post_type_menu_icon = $_POST['menu_icon'];
		if ( $post_type_item->menu_name == '' ) {
			$post_type_menu_name = $_POST['menu_name'];
		} else {
			$post_type_menu_name = $post_type_item->menu_name;
		}
		$post_type_menu_name_str = preg_replace('/[^a-z0-9_s]/i', '', $post_type_menu_name);
	
	
		
		$update_row = "UPDATE $table_name
				SET name ='$post_type_name',
				menu_name ='$post_type_menu_name_str',
				menu_icon ='$post_type_menu_icon',
				description ='$post_type_description',
				taxonomies ='$post_type_taxonomies'
				WHERE post_ID=$post->ID";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		    dbDelta($update_row);  
	
	} else {
	
		$post_type_menu_name = $_POST['menu_name'];
		$post_type_description = $_POST['description'];
		$post_type_menu_icon = $_POST['menu_icon'];
		$post_type_menu_name_str = preg_replace('/[^a-z0-9_s]/i', '', $post_type_menu_name);
		$wpdb->insert($table_name, array(
		        'post_ID' => $post->ID,
		        'name' => $post->post_title,
		        'menu_name' => $post_type_menu_name_str,
		        'menu_icon' => $post_type_menu_icon,
		        'description' => $post_type_description,
		        'taxonomies' => $post_type_taxonomies,
		        
		));
	}
    flush_rewrite_rules();
    }
}

function delete_post_row($delete_row){

    if( get_post_type() == 'spt' ) {

	global $wpdb;
	$post = get_post();
	$table_name = $wpdb->prefix . 'simple_post_type';
	
	$delete_row = "DELETE FROM $table_name WHERE post_ID= $post->ID";
	$wpdb->query($delete_row);
    }
}

function spt_create_meta_boxes() {
    add_meta_box("simple-post-type-item-meta-box", __( 'Post Type Customization', 'simple-post-type' ), array($this, 'spt_item_meta_box'), "spt", "normal", "core", null);
}

public function spt_admin_menu() {
    add_utility_page( __( 'Post Types', 'simple-post-type' ), __( 'Post Types', 'simple-post-type' ), 'manage_options', 'edit.php?post_type=spt', '', 'dashicons-welcome-add-page' );
    add_submenu_page( 'edit.php?post_type=spt', __( 'Add ons', 'simple-post-type' ), __( 'Add ons', 'simple-post-type' ), 'manage_options', 'edit.php?post_type=spt_add_ons' );
}


function spt_load_textdomain() {
    load_plugin_textdomain( 'simple-post-type', false, plugin_basename( SPT__PLUGIN_DIR . 'languages' ) );
}

function spt_update_messages( $messages ) {

		global $post, $post_ID;

		$messages['spt' ] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Custom Post Type updated.', 'simple-post-type' ),
			2 => __( 'Custom Post Type updated.', 'simple-post-type' ),
			3 => __( 'Custom Post Type deleted.', 'simple-post-type' ),
			4 => __( 'Custom Post Type updated.', 'simple-post-type' ),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __( 'Custom Post Type restored to revision from %s', 'simple-post-type' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __( 'Custom Post Type published.', 'simple-post-type' ),
			7 => __( 'Custom Post Type saved.', 'simple-post-type' ),
			8 => __( 'Custom Post Type submitted.', 'simple-post-type' ),
			9 => __( 'Custom Post Type scheduled for.', 'simple-post-type' ),
			10 => __( 'Custom Post Type draft updated.', 'simple-post-type' ),
		);

		return $messages;

}



function register_custom_post_type() {
	
 	global $wpdb;
	$post = get_post();
	$table_name = $wpdb->prefix . 'simple_post_type'; // do not forget about tables prefix
	$result = $wpdb->get_results( "SELECT * FROM $table_name");

	foreach ($result as $post_type_array) {
	
		$post_type_name = $post_type_array->name;
		$post_type_menu_name = $post_type_array->menu_name;
		$post_type_menu_icon = $post_type_array->menu_icon;
		$post_type_id = $post_type_array->post_ID;
		$post_type_taxonomies = $post_type_array->taxonomies;
		$check_category = strpos($post_type_taxonomies, 'category');
                $check_post_tag = strpos($post_type_taxonomies, 'post_tag');
		if ($check_category === false) {
                    $post_type_taxonomies_category = '';
                } else {
                    $post_type_taxonomies_category = 'category';
                }
                if ($check_post_tag === false) {
                    $post_type_taxonomies_post_tag = '';
                } else {
                    $post_type_taxonomies_post_tag = 'post_tag';
                }
		
		
		if ( get_post_status ( $post_type_id ) == 'publish' ) {
		register_post_type( $post_type_menu_name, array(
	            'labels' => array(
			'name'               => _x( $post_type_name, 'post type general name', 'simple-post-type' ),
			'singular_name'      => _x( $post_type_name, 'post type singular name', 'simple-post-type' ),
			'menu_name'          => _x( $post_type_name, 'admin menu', 'simple-post-type' ),
			'name_admin_bar'     => _x( $post_type_name, 'add new on admin bar', 'simple-post-type' ),
			'add_new'            => _x( 'Add New', 'contact', 'simple-post-type' ),
			'add_new_item'       => __( 'Add New Contact', 'simple-post-type' ),
			'new_item'           => __( 'New Contact', 'simple-post-type' ),
			'edit_item'          => __( 'Edit Contact', 'simple-post-type' ),
			'view_item'          => __( 'View Contact', 'simple-post-type' ),
			'all_items'          => __( $post_type_name, 'simple-post-type' ),
			'search_items'       => __( 'Search Contacts', 'simple-post-type' ),
			'parent_item_colon'  => __( 'Parent Contacts:', 'simple-post-type' ),
			'not_found'          => __( 'No conttacts found.', 'simple-post-type' ),
			'not_found_in_trash' => __( 'No contacts found in Trash.', 'simple-post-type' ),
			),
			
			// Frontend // Admin
			'supports'              => array( 'title', 'editor', 'author', 'comments', 'page-attributes', ),
			'taxonomies'            => array( $post_type_taxonomies_category , $post_type_taxonomies_post_tag ),
			'hierarchical'          => true,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 10,
			'menu_icon'             => $post_type_menu_icon,
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => false,		
			'exclude_from_search'   => true,
			'publicly_queryable'    => true,
			'capability_type'       => 'post', 
		) );
		}
	}
}

     
}
 
$YCspt = new YCspt;