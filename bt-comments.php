<?php
/**
 * Plugin Name: BT Comments
 * Description: Show all comments in one page.
 * Author: biztechc
 * Author URI: http://www.biztechconsultancy.com
 * Version: 1.0 
 */
 
add_action('admin_menu', 'bt_comments_create_menu');
function bt_comments_create_menu()
{

    //create new top-level menu
    add_menu_page('BT Comments Settings', 'BT Comments', 'administrator', 'bt-comments', 'bt_comments_settings_page');

    //call register settings function
    add_action( 'admin_init', 'register_bt_comments_settings' );
}


function register_bt_comments_settings() 
{
    //register our settings
    register_setting( 'bt-comments-settings-group', 'bt_post_type' );
    register_setting( 'bt-comments-settings-group', 'bt_pagination' );
    register_setting( 'bt-comments-settings-group', 'bt_comments_per_page' );
    register_setting( 'bt-comments-settings-group', 'bt_exclude_post' );
}

function bt_comments_settings_page() // Admin side page options
{
    
    $set_bt_post_type = get_option('bt_post_type');
    
    if($set_bt_post_type == NULL)
    {
        $set_bt_post_type = Array('bt' => 'bt');
    }
    $set_bt_pagination = get_option('bt_pagination');
    $set_bt_comments_per_page = get_option('bt_comments_per_page');
    
    if($set_bt_comments_per_page == NULL)
    {
        $set_bt_comments_per_page = 10;
    }
    $set_bt_exclude_post = get_option('bt_exclude_post');
    
?>
<div class="wrap">
<h2>BT Comments Settings</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'bt-comments-settings-group' ); ?>
    <?php do_settings_sections( 'bt-comments-settings-group' ); ?>
    <table class="form-table">
    
        <tr valign="top">
            <th scope="row">Post Type</th>
            <td>
                <fieldset>
                             <?php
                                $post_types = get_post_types( '', 'names' );
                                
                                unset($post_types['attachment']);
                                unset($post_types['revision']);
                                unset($post_types['nav_menu_item']);
                                 
                                foreach ( $post_types as $post_type ) 
                                {
                                    if(in_array("$post_type",$set_bt_post_type) == true)
                                    {
                                        $checked = 'checked=checked';
                                    }
                                    ?>
                                        <label><input type="checkbox"  value="<?php echo $post_type; ?>" name="bt_post_type[]" <?php echo  $checked; ?>> <span><?php echo $post_type; ?></span></label><br>
                                    <?php
                                    $checked = ''; 
                                }

                             ?>   
                </fieldset>
            </td>
        </tr>
         
        <tr valign="top">
            <th scope="row">Pagination</th>
            <td>
                <fieldset>
                        <?php 
                            if($set_bt_pagination == 'yes')
                            {
                                ?>
                                    <label><input type="radio"  value="yes" name="bt_pagination" checked="checked"> <span>Yes</span></label><br>
                                    <label><input type="radio"  value="no"  name="bt_pagination"> <span>No</span></label>
                                <?php    
                            }
                            else
                            {
                                ?>
                                    <label><input type="radio"  value="yes" name="bt_pagination"> <span>Yes</span></label><br>
                                    <label><input type="radio"  value="no"  name="bt_pagination" checked="checked"> <span>No</span></label>
                                <?php 
                            }
                        ?>
                </fieldset>
            </td>
        </tr>
        
        <tr valign="top">
            <th scope="row">Comments Per Page</th>
            <td><input type="number" class="small-text" value="<?php echo $set_bt_comments_per_page; ?>"  min="1" step="1" name="bt_comments_per_page"></td>
        </tr>
        
        <tr valign="top">
            <th scope="row">Exclude Post Id</th>
            <td><input type="text" name="bt_exclude_post" value="<?php echo $set_bt_exclude_post; ?>" /> Exclude post id with comma separated. like 11,22,33</td>
        </tr>
        
    </table>
    
    <?php submit_button(); ?>

</form>
</div>
<?php } ?>
<?php 
function custom_comments() // set comments settings 
{         
    $page = intval( get_query_var( 'cpage' ) );
    if ( 0 == $page ) 
    {
        $page = 1;
        set_query_var( 'cpage', $page );
    }
    
    $pagination = get_option('bt_pagination');
    
    if($pagination == 'yes')
    {
       $comments_per_page = get_option('bt_comments_per_page');
    }
    else
    {
        $comments_per_page = 0;
    }   
       
      $post_type = get_option('bt_post_type');
      if($post_type != NULL)
      {          
            function wpse_121051( $clauses, $wpqc )
            {
                    global $wpdb;

                    // Remove the comments_clauses filter, we don't need it anymore. 
                    remove_filter( current_filter(), __FUNCTION__ );

                    // Add the multiple post type support.
                    if( isset( $wpqc->query_vars['post_type'][0] ) )
                    {

                        $join = join( "', '", array_map( 'esc_sql', $wpqc->query_vars['post_type'] ) );

                        $from = "$wpdb->posts.post_type = '" . $wpqc->query_vars['post_type'][0] . "'";                         
                        $to   = sprintf( "$wpdb->posts.post_type IN ( '%s' ) ", $join );

                        $clauses['where'] = str_replace( $from, $to, $clauses['where'] );
                    }  

                    return $clauses;
            }
            add_filter( 'comments_clauses', 'wpse_121051', 10, 2 ); 
      }
      else
      {
         $post_type='post'; 
      }    
    
    $exclude_post = get_option('bt_exclude_post');
    $exclude_post = explode(',',$exclude_post);
        
    $defaults = array(
        'order' => 'DESC',
        'post_id' => 0,
        'post_type' => $post_type,
        'count' => false,
        'number' => '',
        'post__not_in' => $exclude_post,
        'date_query' => null
    );
    $comments = get_comments( $defaults );
    echo "<ul class=custom-comments>";
    wp_list_comments( array (
            'walker'            => null,
            'max_depth'         => '',
            'style'             => 'ul',
            'callback'          => 'custom_comments_template',
            'end-callback'      => null,
            'type'              => 'all',
            'reply_text'        => 'Reply',
            'page'              => $page,
            'per_page'          => $comments_per_page,
            'avatar_size'       => 32,
            'reverse_top_level' => null,
            'reverse_children'  => '',
            'format'            => 'html5', 
            'short_ping'        => false 
        ), $comments );
    echo "</ul>";  
    
    echo "<div class=custom-navigation>";
    paginate_comments_links();
    echo "</div>";
}
add_shortcode('bt_comments','custom_comments');

function custom_comments_style() // add custom style
{
    wp_enqueue_style( 'custom-comments', plugins_url('css/custom-comments.css', __FILE__) );
}  
add_action('wp_enqueue_scripts','custom_comments_style');   

add_filter( 'pre_option_page_comments', '__return_true' );    

function custom_comments_template($comment, $args, $depth)  // show comments
{
    $GLOBALS['comment'] = $comment;
    ?>
        <li>
                <div class="avatar-custom"><?php echo get_avatar( get_the_author_meta( 'ID' ), 50 ); ?></div>
                <div class="custom-comment-wrap">
                    <h4 class="custom-comment-meta">
                        From <span class="custom-comment-author"><?php echo $comment->comment_author; ?></span> 
                        on <span class="custom-comment-on-title"><a href="<?php echo $comment->guid; ?>" target="_blank"><?php echo $comment->post_title; ?></a></span>
                    </h4>
                    <blockquote><?php echo apply_filters ("the_content",$comment->comment_content); ?></blockquote>
                    <span class="custom-comment-link"><a href="<?php echo $comment->guid.'#comment-'.$comment->comment_ID; ?>" target="_blank">Go to comment</a></span>
                </div>
        </li>
    <?php
}

register_uninstall_hook( __FILE__, 'bt_comments_uninstall' ); // uninstall plug-in
function bt_comments_uninstall()
{
   delete_option('bt_post_type');
   delete_option('bt_pagination');
   delete_option('bt_comments_per_page'); 
   delete_option('bt_exclude_post');
} 