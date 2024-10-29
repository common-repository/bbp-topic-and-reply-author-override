<?php
/**
* Plugin Name: bbPress Topic and Reply Author Override
* Version: 1.2
* Plugin URI:  http://wordpress.org/plugins/bbp-topic-and-reply-author-override
* Description: A qucik way to override bbPress topic and reply author
* Author: P. Roy
* Author URI: https://www.proy.info
* License: GPL v3
**/

/**
 * bbPress Topic and Reply Author Override
 * Copyright (C) 2017, P. Roy - contact@proy.info
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class bbPress_Topic_and_Reply_Author_Override {

    /**
     * Constructor.
     */

	private $match_slugs = array();

    public function __construct() {
        if ( is_admin() ) {
            add_action( 'load-post.php',     array( $this, 'init_metabox' ) );
            add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );
            $this->match_slugs = array('topic', 'reply');
        }
    }

    /**
     * Meta box initialization.
     */
    public function init_metabox() {
        add_action( 'add_meta_boxes', array( $this, 'add_metabox'  )        );
        add_action( 'save_post',      array( $this, 'save_metabox' ), 100, 2 );
    }

    /**
     * Adds the meta box.
     */
    public function add_metabox() {
        add_meta_box(
            'bbp_author_metabox',
            __( 'Topic Author Override', 'textdomain' ),
            array( $this, 'render_metabox' ),
            'topic', 'side', 'high'
        );
        add_meta_box(
            'bbp_author_metabox',
            __( 'Reply Author Override', 'textdomain' ),
            array( $this, 'render_metabox' ),
            'reply', 'side', 'high'
        );

    }

    /**
     * Renders the meta box.
     */
    public function render_metabox( $post ) {
    	// Add nonce for security and authentication.
        wp_nonce_field( 'custom_nonce_action', 'custom_nonce' );

        $post_author_override = $post->post_author;
        //$post_author_override = get_post_meta($post->ID, 'post_author_override', true);
        //echo $post_author_override;

        if (is_admin() && $this->is_edit_page('new')){
        	$post_author_override = wp_get_current_user()->ID;
        }

        $users = get_users();
		$user_select = '<select id="bbp_author_override_metabox" name="post_author_override" class="">';

        if($post_author_override ==0){
            $_bbp_anonymous_name = get_post_meta($post->ID, '_bbp_anonymous_name', true);
            $user_select .= '<option value="0" selected="selected">Anonymous/Guest'.($_bbp_anonymous_name?' ('.$_bbp_anonymous_name.')':'').'</option>';
        }
		//Leave the admin in the list
		foreach($users as $user) {
			//print_r($user);
			$selected = ($post_author_override == $user->ID)?'selected="selected"':'';
			$user_select .= '<option value="'.$user->ID.'"'.$selected.'>'.$user->display_name.' ('.$user->user_login.')</option>';
		}
		$user_select .='</select>';

		echo $user_select;
    }

    /**
     * Handles saving the meta box.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     * @return null
     */
    public function save_metabox( $post_id, $post ) {
        // Add nonce for security and authentication.
        $nonce_name   = isset( $_POST['custom_nonce'] ) ? $_POST['custom_nonce'] : '';
        $nonce_action = 'custom_nonce_action';

        // Check if nonce is set.
        if ( ! isset( $nonce_name ) ) {
            return;
        }

        // Check if nonce is valid.
        if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
            return;
        }

        // Check if user has permissions to save data.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Check if not an autosave.
        if ( wp_is_post_autosave( $post_id ) ) {
            return;
        }

        // Check if not a revision.
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }


        // Check to match the slug
        if(!in_array($post->post_type, $this->match_slugs)){
        	return;
    	}

    	/* $meta_box_text_value = "";

	    if(isset($_POST["post_author_override"])) {
	        $meta_box_text_value = $_POST["post_author_override"];
	    }
	    update_post_meta($post_id, "post_author_override", $meta_box_text_value); */

        if ( ! wp_is_post_revision( $post_id ) ){

            // unhook this function so it doesn't loop infinitely
            //remove_action('save_post','change_pos_auth');

            if ( isset($_POST['post_author_override']) ) {
                //$args = array('ID'=>$post_id,'post_author'=>$_POST['post_author_override']);
                //wp_update_post( $args );
                // re-hook this function
                //add_action('save_post','change_pos_auth');
                global $wpdb;
	            $wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix . "posts` SET `post_author` = '".$_POST['post_author_override']."' WHERE `ID` = ".$post_id));
            }
        }

	}

	/**
	* is_edit_page
 	* function to check if the current page is a post edit page
 	*/
	public function is_edit_page($new_edit = null){
	    global $pagenow;
	    //make sure we are on the backend
	    if (!is_admin()) return false;


	    if($new_edit == "edit")
	        return in_array( $pagenow, array( 'post.php',  ) );
	    elseif($new_edit == "new") //check for new post page
	        return in_array( $pagenow, array( 'post-new.php' ) );
	    else //check for either new or edit
	        return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
	}
}

new bbPress_Topic_and_Reply_Author_Override();
?>
