<?php

if (!class_exists('WP_List_Table')) {
    require_once ( ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Class Wpil_Table_Domain
 */
class Wpil_Table_Domain extends WP_List_Table
{
    function get_columns()
    {

        $options = get_user_meta(get_current_user_id(), 'report_options', true);

        $columns = array(
            'host' => __('Domain', 'wpil')
        );

        if(!isset($options['show_link_attrs']) || $options['show_link_attrs'] === 'on'){
            $attrs_help_overlay = 'class="wpil-report-header-container wpil-is-tooltipped wpil-no-scale wpil-tooltip-target-parent wpil-tooltip-target.column-attributes" data-wpil-tooltip-read-time="9500" ' . Wpil_Toolbox::generate_tooltip_text('domain-report-table-attr-col');

            $columns['attributes'] = 
            '<div ' . $attrs_help_overlay . '>' . 
                __('Applied Domain Attributes', 'wpil') . 
                '<div class="wpil-report-header-tooltip">
                    <div class="wpil_help">
                        <i class="dashicons dashicons-editor-help"></i>
                        <div class="wpil-help-text" style="display: none; width: 300px">' . 
                            __('These are the attributes that are being actively applied to the listed domain\'s links by Link Whisper.', 'wpil') . 
                            '<br><br>' . 
                            __('The attributes are added to the links in content as it\'s being rendered for display, and overrides any manually created attributes.', 'wpil') . 
                            '<br><br>' . 
                            __('So for example, if you see "nofollow" listed in a field for a domain, that means Link Whisper is adding \'rel="nofollow"\' to links that point to that domain, and removing \'rel="dofollow"\' from the links if it\'s present.', 'wpil') .
                            '<br><br>' . 
                            __('If you change or remove an attribute from a domain, Link Whisper will stop applying the attribute to the domain\'s links.', 'wpil') . '</div>
                    </div>
                </div>
            </div>';
        }

        $posts_help_overlay = 'class="wpil-report-header-container wpil-is-tooltipped wpil-no-scale wpil-tooltip-target-parent wpil-tooltip-target.column-posts" ' . Wpil_Toolbox::generate_tooltip_text('domain-report-table-posts-col');
        $columns['posts'] = '<div ' . $posts_help_overlay . '>' . 
                                __('Posts', 'wpil') . 
                            '</div>';
        
        $links_help_overlay = 'class="wpil-report-header-container wpil-is-tooltipped wpil-no-scale wpil-tooltip-target-parent wpil-tooltip-target.column-links" data-wpil-tooltip-read-time="9500" ' . Wpil_Toolbox::generate_tooltip_text('domain-report-table-links-col');
        $columns['links'] = '<div ' . $links_help_overlay . '>' . 
            __('Links', 'wpil') . 
        '</div>';

        return $columns;
    }

    function prepare_items()
    {
        define('WPIL_LOADING_REPORT', true);
        $options = get_user_meta(get_current_user_id(), 'report_options', true);
        $per_page = !empty($options['per_page']) ? $options['per_page'] : 20;
        $page = isset($_REQUEST['paged']) ? (int)$_REQUEST['paged'] : 1;
        $search = !empty($_GET['s']) ? $_GET['s'] : '';
        $search_type = !empty($_GET['domain_search_type']) ? $_GET['domain_search_type'] : 'domain';
        $show_attributes = !isset($options['show_link_attrs']) || $options['show_link_attrs'] === 'on' ? true: false;
        $show_untargeted = isset($_GET['show_untargeted']) && $_GET['show_untargeted'] == 'on' ? 1 : 0;

        if(!isset($_REQUEST['paged']) && empty($search) && empty($orderby) && empty($order) && empty($show_untargeted)){
            Wpil_Telemetry::log_event('report_open_domains');
        }

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = [];
        $this->_column_headers = [$columns, $hidden, $sortable];
        if($show_untargeted){
            Wpil_Telemetry::log_event_for_user('domains_show_untargeted');
        }
        $data = Wpil_Dashboard::getDomainsData($per_page, $page, $search, $search_type, $show_attributes, false, $show_untargeted);
        $this->items = $data['domains'];

        $this->set_pagination_args(array(
            'total_items' => $data['total'],
            'per_page' => $per_page,
            'total_pages' => ceil($data['total'] / $per_page)
        ));
    }

    function column_default($item, $column_name)
    {
        switch($column_name) {
            case 'host':
                return '<a href="'.$item['protocol'] . $item[$column_name].'" target="_blank">'. $item['protocol'] . $item[$column_name].'</a>';
            case 'attributes':
                $available_attrs = Wpil_Settings::get_available_link_attributes();
                $active_attrs = $item[$column_name];
                $options = '';

                foreach($available_attrs as $attr => $name){
                    $selected = in_array($attr, $active_attrs, true) ? 'selected="selected"': '';
                    $options .= '<option ' . $selected . ' value="' . esc_attr($attr) . '"' . ((Wpil_Settings::check_if_attrs_conflict($attr, $active_attrs)) ? 'disabled="disabled"': '') . '>' . $name . '</option>';
                }

                $button_panel = 
                '<div>
                    <select multiple class="wpil-domain-attribute-multiselect">' . $options . '</select>
                    <button class="wpil-domain-attribute-save button-disabled" data-domain="' . esc_attr($item['host']) . '" data-saved-attrs="' . esc_attr(json_encode($active_attrs)) . '" data-nonce="' . wp_create_nonce(get_current_user_id() . 'wpil_attr_save_nonce') . '">' .__('Update','wpil'). '</button>
                </div>';

                return $button_panel;
            case 'posts':
                $posts = $item[$column_name];

                $list = '<ul class="report_links">';
                $post_count = 0;
                foreach ($posts as $post) {
                    if($post_count > 100){
                        break;
                    }
                    $list .= '<li>'
                                . esc_html($post->getTitle()) . '<br>
                                <a href="' . admin_url('post.php?post=' . (int)$post->id . '&action=edit') . '" target="_blank">[edit]</a> 
                                <a href="' . esc_url($post->getLinks()->view) . '" target="_blank">[view]</a><br><br>
                              </li>';
                    $post_count++;
                }
                $list .= '</ul>';

                return '<div class="wpil-collapsible-wrapper" data-wpil-collapsible-host="' . $item['host'] . '" data-wpil-collapsible-nonce="' . wp_create_nonce(wp_get_current_user()->ID . 'wpil-collapsible-nonce') . '">
  			                <div class="wpil-collapsible wpil-collapsible-static wpil-links-count ' . ((!empty($posts)) ? 'wpil-collapsible-has-data': 'wpil-collapsible-no-data') . '">'.count($posts).'</div>
  				            <div class="wpil-content">'.$list.'</div>
  				        </div>';
            case 'links':
                $links = $item[$column_name];

                $list = '<ul class="report_links">';
                foreach ($links as $link) {
                    if(empty($link)){
                        continue;
                    }
                    $list .= '<li>
                                <input type="checkbox" class="wpil_link_select" data-post_id="'.$link->post->id.'" data-post_type="'.$link->post->type.'" data-anchor="' . esc_attr(base64_encode($link->anchor)) . '" data-url="'.base64_encode($link->url).'">
                                <div>
                                    <div style="margin: 3px 0;"><b>Post Title:</b> <a href="' . esc_url($link->post->getLinks()->view) . '" target="_blank">' . esc_html($link->post->getTitle()) . '</a></div>
                                    <div style="margin: 3px 0;"><b>URL:</b> <a href="' . esc_url($link->url) . '" target="_blank">' . esc_html($link->url) . '</a></div>
                                    <div style="margin: 3px 0;"><b>Anchor Text:</b> <a href="' . esc_url(add_query_arg(['wpil_admin_frontend' => '1', 'wpil_admin_frontend_data' => $link->create_scroll_link_data()], $link->post->getLinks()->view)) . '" target="_blank">' . esc_html($link->anchor) . ' <span class="dashicons dashicons-external" style="position: relative;top: 3px;"></span></a></div>
                                    ' . Wpil_Report::get_dropdown_icons($link->post, $link);
                                if('related-post-link' !== Wpil_Toolbox::get_link_context($link->link_context)){
                    $list .=        '<a href="#" class="wpil_edit_link" target="_blank">[' . __('Edit URL', 'wpil') . ']</a>
                                    <div class="wpil-domains-report-url-edit-wrapper">
                                        <input class="wpil-domains-report-url-edit" type="text" value="' . esc_attr($link->url) . '">
                                        <button class="wpil-domains-report-url-edit-confirm wpil-domains-edit-link-btn" data-link_id="' . $link->link_id . '" data-post_id="'.$link->post->id.'" data-post_type="'.$link->post->type.'" data-anchor="' . esc_attr($link->anchor) . '" data-url="'.esc_url($link->url).'" data-nonce="' . wp_create_nonce('wpil_report_edit_' . $link->post->id . '_nonce_' . $link->link_id) . '">
                                            <i class="dashicons dashicons-yes"></i>
                                        </button>
                                        <button class="wpil-domains-report-url-edit-cancel wpil-domains-edit-link-btn">
                                            <i class="dashicons dashicons-no"></i>
                                        </button>
                                    </div>';
                                }
                    $list .=   '</div>
                            </li>';
                }
                $list .= '</ul>';

                $delete_bar = (!empty($links)) ? 
                '<div class="update-post-links">
                    <a href="#" class="button-primary wpil-delete-selected-links disabled" style="margin: 0 0 0 10px;" data-nonce="' . wp_create_nonce(wp_get_current_user()->ID . 'delete-selected-links') . '">' . __('Delete Selected', 'wpil') . '</a>
                    <div style="float: right; display: inline-block;"><strong style="margin: 0 10px 0 0;">Select All</strong><input class="wpil-select-all-dropdown-links" style="margin: 0 10px 0 0;" type="checkbox"></div>
                </div>': '';

                return '<div class="wpil-collapsible-wrapper" data-wpil-collapsible-host="' . $item['host'] . '" data-wpil-collapsible-nonce="' . wp_create_nonce(wp_get_current_user()->ID . 'wpil-collapsible-nonce') . '">
  			                <div class="wpil-collapsible wpil-collapsible-static wpil-links-count ' . ((!empty($links)) ? 'wpil-collapsible-has-data': 'wpil-collapsible-no-data') . '"><span class="wpil_ul">'.count($links).'</span></div>
  				            <div class="wpil-content">'.$list.'</div>
                            ' . $delete_bar . '
  				        </div>';
            default:
                return print_r($item, true);
        }
    }

    function extra_tablenav( $which ) {
        if ($which == "bottom") {
            ?>
            <div class="alignright actions bulkactions detailed_export">
                <a href="javascript:void(0)" class="button-primary csv_button" data-type="domains" id="wpil_cvs_export_button" data-file-name="<?php esc_attr_e('detailed-domain-export.csv', 'wpil'); ?>">Detailed Export to CSV</a>
            </div>
            <?php
        }
    }

    public function search_box( $text, $input_id ) {
        if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
            return;
        }

        $input_id = $input_id . '-search-input';

        if(!empty($_REQUEST['orderby'])){
            echo '<input type="hidden" name="orderby" value="' . esc_attr($_REQUEST['orderby']) . '" />';
        }
        if(!empty($_REQUEST['order'])){
            echo '<input type="hidden" name="order" value="' . esc_attr($_REQUEST['order']) . '" />';
        }
        if(!empty($_REQUEST['post_mime_type'])){
            echo '<input type="hidden" name="post_mime_type" value="' . esc_attr($_REQUEST['post_mime_type']) . '" />';
        }
        if(!empty($_REQUEST['detached'])){
            echo '<input type="hidden" name="detached" value="' . esc_attr($_REQUEST['detached']) . '" />';
        }

        $search_type = isset($_REQUEST['domain_search_type']) && !empty($_REQUEST['domain_search_type']) ? $_REQUEST['domain_search_type']: 'domain';
        
        $show_untargeted = (isset($_REQUEST['show_untargeted']) && !empty($_REQUEST['show_untargeted'])) ? true: false;
        ?>
<p class="search-box wpil-is-tooltipped wpil-no-scale wpil-tooltip-no-position" data-wpil-tooltip-read-time="4500" <?php echo Wpil_Toolbox::generate_tooltip_text('domain-report-search'); ?>>
    <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo $text; ?>:</label>
    <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s" value="<?php _admin_search_query(); ?>" />
        <?php submit_button($text, '', '', false, array('id' => 'search-submit')); ?>
    <br />
    <span>
        <span style="display: inline-block; float: left;">
            <span class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-no-position" style="display:inline-block" data-wpil-tooltip-read-time="3500" <?php echo Wpil_Toolbox::generate_tooltip_text('domain-report-search-domains'); ?>>
                <label class="" for="wpil-domain-search-host"><?php esc_html_e('Domain', 'wpil'); ?></label>
                <input type="radio" id="wpil-domain-search-host" name="domain_search_type" value="domain" <?php checked($search_type, 'domain');?>>
            </span>
            <span class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-no-position" style="display:inline-block" data-wpil-tooltip-read-time="3500" <?php echo Wpil_Toolbox::generate_tooltip_text('domain-report-search-links'); ?>>
                <label class="" for="wpil-domain-search-path"><?php esc_html_e('Links', 'wpil'); ?></label>
                <input type="radio" id="wpil-domain-search-path" name="domain_search_type" value="links" <?php checked($search_type, 'links');?>>
            </span>
            <br>
            <span class="wpil-is-tooltipped wpil-no-scale wpil-tooltip-no-position" style="display:inline-block" data-wpil-tooltip-read-time="3500" <?php echo Wpil_Toolbox::generate_tooltip_text('domain-report-search-show-untargetted'); ?>>
                <label class="" for="wpil-domain-show-untargeted"><?php esc_html_e('Show Untargeted Links', 'wpil'); ?></label>
                <input type="checkbox" id="wpil-domain-show-untargeted" class="wpil-tippy-tooltipped" data-wpil-tooltip-content="<?php esc_attr_e('"Show Untargeted Links" tells the report to show internal links that aren\'t pointing to known posts.', 'wpil')?>" name="show_untargeted" <?php checked($show_untargeted);?>>
            </span>
        </span>
        <span>

        </span>
    </span>
</p>
        <?php
    }
}
