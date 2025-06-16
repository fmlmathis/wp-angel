<?php
/**
 * Link Whisper Related Posts Template
 * 
 * To override with a custom template, please create your override file named 'related-posts.php' in your child theme inside:
 * {theme main folder}/templates/link-whisper/frontend/
 * 
 **/
$settings = $data['settings'];
$full_styling = $settings['styling']['full'];
$mobile_styling = $settings['styling']['mobile'];
$title = trim($settings['widget_text']['title']);
$title_tag = in_array($settings['widget_text']['title_tag'], array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div'), true) ? $settings['widget_text']['title_tag']: 'h3';
$description = trim($settings['widget_text']['description']);
$no_posts_message = trim($settings['widget_text']['empty_message']);
$layout_display = $settings['widget_layout']['display'];
$layout_count = $settings['widget_layout']['count'];
$hide_empty = !empty($settings['hide_empty_widget']);
$links = $data['links'];

// if the RP widget is empty && the empty RP widget message should only be displayed to admins && the current user is an admin
$show_admin = ($hide_empty && empty($links) && current_user_can(apply_filters('wpil_filter_main_permission_check', 'manage_categories', false)));

if(!empty($links) && !empty($settings['link_count'])){
    $links = array_slice($links, 0, $settings['link_count']);
}

// if the list item creator doesn't already exist
if(!function_exists('wpil_create_related_post_item')){
    // create it now
    function wpil_create_related_post_item($link_data = array(), $settings = array()){
        $item = '';
        if(empty($link_data) || empty($settings)){
            return $item;
        }

        switch ($settings['widget_layout']['display']) {
            case 'row':
                $item_type = 'div';
                break;
            case 'column':
            default:
            $item_type = 'li';
                break;
        }

        // all journeys begin with a single list item
        $item = '<'.$item_type.' class="lwrp-list-item">';

        // does the user want a thumbnail?
        if($settings['use_thumbnail'] === 1){
            // if he does, grab the thumbnail
            $thumbnail_size = array();
            if(!empty($settings['thumbnail_size'])){
                $size = ($settings['thumbnail_size'] > 480) ? $settings['thumbnail_size']: 480; // use 480 as the min to accomodate mobile views
                $thumbnail_size = array($size, $size);
            }
            $thumbnail = get_the_post_thumbnail($link_data['post_id'], $thumbnail_size);

            // if there's no thumbnail, but the user is willing to use the first image of a post instead
            if(empty($thumbnail) && $settings['use_first_img'] === 1){
                $thumb_url = (!empty($link_data['thumb_url'])) ? $link_data['thumb_url']: Wpil_Widgets::extract_first_image_url(get_post($link_data['post_id']));

                // if we've got an image url
                if(!empty($thumb_url)){
                    // grab that instead
                    $thumbnail_size = '';
                    if(!empty($settings['thumbnail_size'])){
                        $size = ($settings['thumbnail_size'] > 480) ? $settings['thumbnail_size']: 480; // use 480 as the min to accomodate mobile views
                        $thumbnail_size = 'width="' . $size . '"';
                    }
                    $thumbnail = '<img ' . $thumbnail_size . ' src="' . esc_url($thumb_url) . '">';
                }
            }

            // if there is a thumbnail
            if(!empty($thumbnail)){
                // where should it go?
                if($settings['thumbnail_position'] === 'above'){
                    // put it above the link text
                    $item .= '<a href="' . esc_attr($link_data['url']) . '" class="lwrp-list-link">';
                    $item .= $thumbnail;
                    $item .= '<br>';
                    $item .= '<span class="lwrp-list-link-title-text">' . esc_html($link_data['anchor']) . '</span>';
                    $item .= '</a>';
                }elseif($settings['thumbnail_position'] === 'below'){
                    $item .= '<a href="' . esc_attr($link_data['url']) . '" class="lwrp-list-link">';
                    $item .= '<span class="lwrp-list-link-title-text">' . esc_html($link_data['anchor']) . '</span>';
                    $item .= '<br>';
                    $item .= $thumbnail;
                    $item .= '</a>';
                    // put it below the link text
                }elseif($settings['thumbnail_position'] === 'inside'){
                    // or put it inside the link in place of text
                    $item .= '<a href="' . esc_attr($link_data['url']) . '" title="' . esc_attr(get_the_title($link_data['post_id'])) . '" class="lwrp-list-link">' . $thumbnail . '</a>';
                }
            }else{
                // if there's no thumbnail, create the link!
                $item .= '<a href="' . esc_attr($link_data['url']) . '" class="lwrp-list-link"><span class="lwrp-list-link-title-text">' . esc_html($link_data['anchor']) . '</span></a>';
            }
        }else{
            // if no thumbnail is desired, create the link!
            $item .= '<a href="' . esc_attr($link_data['url']) . '" class="lwrp-list-link"><span class="lwrp-list-link-title-text">' . esc_html($link_data['anchor']) . '</span></a>';
        }

        // close out the list item
        $item .= '</'.$item_type.'>';

        // and return our item
        return $item;
    }
}

// If...
if(
    !empty($links) || // we have links, OR
    (empty($links) && !$hide_empty) || // we don't have links but we're supposed to show the widget anyway, OR
    ($show_admin) // we don't have links, and we're not supposed to show the related post, but the user is an admin
){
    // show the Related Posts widget
?>
<style>
<?php echo Wpil_Widgets::generate_related_post_styles($settings); ?>
</style>
<div id="link-whisper-related-posts-widget" class="link-whisper-related-posts lwrp">
    <?php if(!empty($title)){ ?>
        <?php echo '<' . $title_tag . ' class="lwrp-title">' . esc_html($title) . '</' . $title_tag . '>'; ?>
    <?php } ?>

    <?php if(!empty($description)){ ?>
        <?php echo '<div class="lwrp-description">' . str_replace("\n", '<br>', esc_html($description)) . '</div>'; ?>
    <?php } ?>
    <div class="lwrp-list-container">
        <?php
        if(empty($links)){
            if($show_admin){
                $no_posts_message .= ' (' . __('Widget Only Visible to Admins.', 'wpil') . ')';
            }

            if(!$hide_empty || $show_admin){
                ?>
                <div class="lwrp-list-item lwrp-no-posts-message-item">
                    <span class="lwrp-list-no-posts-message"><?php echo str_replace("\n", '<br>', esc_html($no_posts_message)); ?></span>
                </div>
                <?php
            }

        }else{
        ?>
        <?php if($layout_display === 'column'){ ?>
            <?php if($layout_count === 1){ ?>
                <ul class="lwrp-list lwrp-list-single">
                    <?php
                    foreach($links as $link_data){
                        echo wpil_create_related_post_item($link_data, $settings);
                    }
                    ?>
                </ul>
            <?php }elseif($layout_count === 2){ ?>
                <div class="lwrp-list-multi-container">
                    <ul class="lwrp-list lwrp-list-double lwrp-list-left">
                        <?php
                        foreach($links as $ind => $link_data){
                            if($ind < ceil(count($links)/2)){
                                echo wpil_create_related_post_item($link_data, $settings);
                            }
                        }
                        ?>
                    </ul>
                    <ul class="lwrp-list lwrp-list-double lwrp-list-right">
                        <?php
                        foreach($links as $ind => $link_data){
                            if($ind >= ceil(count($links)/2)){
                                echo wpil_create_related_post_item($link_data, $settings);
                            }
                        }
                        ?>
                    </ul>
                </div>
            <?php }elseif($layout_count === 3){
                $parts = array_chunk($links, ceil(count($links)/3));
            ?>
                <div class="lwrp-list-multi-container">
                    <ul class="lwrp-list lwrp-list-triple lwrp-list-left">
                        <?php
                        if(isset($parts[0]) && !empty($parts[0])){
                            foreach($parts[0] as $ind => $link_data){
                                echo wpil_create_related_post_item($link_data, $settings);
                            }
                        }
                        ?>
                    </ul>
                    <ul class="lwrp-list lwrp-list-triple lwrp-list-center">
                        <?php
                        if(isset($parts[1]) && !empty($parts[1])){
                            foreach($parts[1] as $ind => $link_data){
                                echo wpil_create_related_post_item($link_data, $settings);
                            }
                        }
                        ?>
                    </ul>
                    <ul class="lwrp-list lwrp-list-triple lwrp-list-right">
                        <?php
                        if(isset($parts[2]) && !empty($parts[2])){
                            foreach($parts[2] as $ind => $link_data){
                                echo wpil_create_related_post_item($link_data, $settings);
                            }
                        }
                        ?>
                    </ul>
                </div>
            <?php } ?>
        <?php }elseif($layout_display === 'row'){
            $parts = array_chunk($links, ceil(count($links)/$layout_count));
            $classes = array(0 => 'lwrp-list-single-row', 1 => 'lwrp-list-single-row', 2 => 'lwrp-list-double-row', 3 => 'lwrp-list-triple-row');
            $class = isset($classes[count($parts)]) ? $classes[count($parts)]: 'lwrp-list-single-row';
            $max = 0;
            foreach($parts as $links){ 
                $count = count($links);
                if($max < $count){
                    $max = $count;
                }
                ?>
                <div class="lwrp-list lwrp-list-row-container <?php echo $class;?>">
                <?php
                foreach($links as $ind => $link_data){
                    echo wpil_create_related_post_item($link_data, $settings);
                }
                if($max > $count){
                    for($i = $count; $i < $max; $i++){
                        echo '<div class="lwrp-list-item lwrp-empty-list-item"></div>';
                    }
                }
                ?>
                </div>
            <?php
            }
        } 
        
    }?>
    </div>
</div>
<?php
}
?>