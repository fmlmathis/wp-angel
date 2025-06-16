<?php

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Wpil_Table_Report extends WP_List_Table
{

    function __construct()
    {
        parent::__construct(array(
            'singular' => __('Linking Stats', 'wpil'),
            'plural' => __('Linking Stats', 'wpil'),
            'ajax' => false
        ));

        $this->prepare_items();
    }

    function column_default($item, $column_name)
    {
        if ($column_name == 'post_type') {
            return $item['post']->getType();
        }

        if (!array_key_exists($column_name, $item)) {
            return "<i>(not set)</i>";
        }

        if($column_name === 'organic_traffic'){
            return '<ul>
                        <li>' . __('Clicks: ', 'wpil') . $item['organic_traffic'] . '</li>
                        <li>' . __('AVG Position: ', 'wpil') . $item['position'] . '</li>
                    </ul>';
        }

        $v = $item[$column_name];
        if (!$v) {
            $v = 0;
        }

        $v_num = $v;

        $post_id = $item['post']->id;
        $post_type = $item['post']->type;
        if (in_array($column_name, Wpil_Report::$meta_keys)) {
            $opts = [];
            $opts['target'] = '_blank';
            $opts['style'] = 'text-decoration: underline';

            $opts['data-wpil-report-post-id'] = $post_id;
            $opts['data-wpil-report-type'] = $column_name;
            $opts['data-wpil-report'] = 1;

            $v = "<span class='wpil_ul'>$v</span>";

            switch ($column_name) {
                case WPIL_LINKS_INBOUND_INTERNAL_COUNT:
                    $v = "<div class='inbound-link-count'>&#x2799;";
                    $links_data = $item['post']->getInboundInternalLinks();
                    $title = __('Inbound Internal Links', 'wpil');
                    break;

                case WPIL_LINKS_OUTBOUND_EXTERNAL_COUNT:
                    $v = "&#x279A;";
                    $links_data = $item['post']->getOutboundExternalLinks();
                    $title = __('Outbound External Links', 'wpil');
                    break;

                case WPIL_LINKS_OUTBOUND_INTERNAL_COUNT:
                    $v = "<div class='outbound-internal-link-count'>&#x2799;";
                    $links_data = $item['post']->getOutboundInternalLinks();
                    $title = __('Outbound Internal Links', 'wpil');
                    break;
            }


            if ($v_num > 0 || WPIL_LINKS_INBOUND_INTERNAL_COUNT == $column_name || WPIL_LINKS_OUTBOUND_INTERNAL_COUNT == $column_name) {

            } else {
                $v = "<div title='" . esc_attr($title) . "' style='margin:0px; text-align: center; padding: 5px'>0 $v</div>";
            }

            $get_all_links = Wpil_Settings::showAllLinks();

            if ($v_num > 0 || WPIL_LINKS_INBOUND_INTERNAL_COUNT == $column_name || WPIL_LINKS_OUTBOUND_INTERNAL_COUNT == $column_name) {
                $rep = '';

                if (is_array($links_data)) {
                    $rep .= '<ul class="report_links">';

                    switch ($column_name) {
                        case 'wpil_links_inbound_internal_count':
                            $count = 0;
                            foreach ($links_data as $link) {
                                if (!Wpil_Filter::linksLocation() || $link->location == Wpil_Filter::linksLocation()) {
                                    $count++;
                                    if($count > 100){
                                        continue;
                                    }
                                    if (!empty($link->post)) {
                                        $rep .= '<li>
                                                    <input type="checkbox" class="wpil_link_select" data-post_id="'.$link->post->id.'" data-post_type="'.$link->post->type.'" data-anchor="'.base64_encode($link->anchor).'" data-url="'.base64_encode($link->url).'">
                                                    <div>
                                                        <div style="margin: 3px 0;"><b>Origin Post Title:</b> ' . esc_html($link->post->getTitle()) . '</div>
                                                        <div style="margin: 3px 0;"><b>Anchor Text:</b> <a href="' . esc_url(add_query_arg(['wpil_admin_frontend' => '1', 'wpil_admin_frontend_data' => $link->create_scroll_link_data()], $link->post->getLinks()->view)) . '" target="_blank">' . esc_html($link->anchor) . ' <span class="dashicons dashicons-external" style="position: relative;top: 3px;"></span></a></div>';
                                        $rep .= ($get_all_links) ? '<div style="margin: 3px 0;"><b>Link Location:</b> ' . $link->location . '</div>' : '';
                                        $rep .= Wpil_Report::get_dropdown_icons($link->post, $link, 'inbound-internal');
                                        $rep .=         '<a href="' . admin_url('post.php?post=' . $link->post->id . '&action=edit') . '" target="_blank">[edit]</a> 
                                                        <a href="' . esc_url($link->post->getLinks()->view) . '" target="_blank">[view]</a>
                                                        <br>
                                                    </div>
                                                </li>';
                                    } else {
                                        $rep .= '<li><div><b>[' . esc_html(strip_tags($link->anchor)) . ']</b><br>[' . $link->location . ']<br><br></div></li>';
                                    }
                                }
                            }

                            $add_inbound_links = (isset($item['links_inbound_page_url']) && !empty($item['links_inbound_page_url'])) ? '<a class="add-internal-links" href="javascript:void(window.open(\''. esc_url($item['links_inbound_page_url']) .'\'))" style="text-decoration: underline;" data-wpil-report-post-id="1" data-wpil-report-type="wpil_links_inbound_internal_count" data-wpil-report="1">Add</a>': '';
                            $v .= '<span class="wpil_ul">' . $count . '</span></div>' . $add_inbound_links;
                            break;
                        case 'wpil_links_outbound_internal_count':
                            $count = 0;
                            foreach ($links_data as $link) {
                                if (!Wpil_Filter::linksLocation() || $link->location == Wpil_Filter::linksLocation()) {
                                    $count++;
                                    if($count > 100){
                                        continue;
                                    }

                                    $primary_category_note = '';
                                    if(!empty($link->post) && $link->post->type === 'post') {
                                        // Get the main term
                                        $post_type = $link->post->getRealType();
                                        $primary_term = Wpil_Post::get_primary_term_for_main_taxonomy($link->post->id, $post_type);

                                        if ($primary_term instanceof WP_Term) {
                                            $primary_category_note = '<div style="margin: 3px 0;"><b>Main Category:</b> ' . esc_html($primary_term->name) . '</div>';
                                        } else {
                                            $primary_category_note = '<div style="margin: 3px 0;"><b>Main Category:</b> None assigned.</div>';
                                        }
                                    }
                                    $rep .= '<li>
                                                <input type="checkbox" class="wpil_link_select" data-post_id="' . $item['post']->id . '" data-post_type="' . $item['post']->type . '" data-anchor="' . base64_encode($link->anchor) . '" data-url="' . base64_encode($link->url) . '">
                                                <div>
                                                    <div style="margin: 3px 0;"><b>Link:</b> <a href="' . esc_url($link->url) . '" target="_blank" style="text-decoration: underline">' . esc_html($link->url) . '</a></div>
                                                    <div style="margin: 3px 0;"><b>Anchor Text:</b> <a href="' . esc_url(add_query_arg(['wpil_admin_frontend' => '1', 'wpil_admin_frontend_data' => $link->create_scroll_link_data()], $item['post']->getLinks()->view)) . '" target="_blank">' . esc_html($link->anchor) . ' <span class="dashicons dashicons-external" style="position: relative;top: 3px;"></span></a></div>';
                                    $rep .= ($get_all_links) ? '<div style="margin: 3px 0;"><b>Link Location:</b> ' . $link->location . '</div>' : '';
                                    $rep .= $primary_category_note;
                                    $rep .= Wpil_Report::get_dropdown_icons($item['post'], $link, 'outbound-internal');
                                    $rep .=     '</div>
                                            </li>';
                                }
                            }
                            $edit_link = '<a class="add-outbound-internal-links" href="javascript:void(window.open(\''. esc_url($item['post']->getLinks()->edit) .'\'))" style="text-decoration: underline;">Add</a>';
                            $v .= '<span class="wpil_ul">' . $count . '</span></div>' . $edit_link;
                            break;
                        case 'wpil_links_outbound_external_count':
                            $count = 0;
                            foreach ($links_data as $link) {
                                if (!Wpil_Filter::linksLocation() || $link->location == Wpil_Filter::linksLocation()) {
                                    $count++;
                                    if($count > 100){
                                        continue;
                                    }
                                    $rep .= '<li>
                                                <input type="checkbox" class="wpil_link_select" data-post_id="' . $item['post']->id . '" data-post_type="' . $item['post']->type . '" data-anchor="' . base64_encode($link->anchor) . '" data-url="' . base64_encode($link->url) . '">
                                                <div>
                                                    <div style="margin: 3px 0;"><b>Link:</b> <a href="' . esc_url($link->url) . '" target="_blank" style="text-decoration: underline">' . esc_html($link->url) . '</a></div>
                                                    <div style="margin: 3px 0;"><b>Anchor Text:</b> <a href="' . esc_url(add_query_arg(['wpil_admin_frontend' => '1', 'wpil_admin_frontend_data' => $link->create_scroll_link_data()], $item['post']->getLinks()->view)) . '" target="_blank">' . esc_html($link->anchor) . ' <span class="dashicons dashicons-external" style="position: relative;top: 3px;"></span></a></div>';
                                    $rep .= ($get_all_links) ? '<div style="margin: 3px 0;"><b>Link Location:</b> ' . $link->location . '</div>' : '';
                                    $rep .= Wpil_Report::get_dropdown_icons(array(), $link, 'outbound-external');
                                    $rep .=     '</div>
                                            </li>';
                                }
                            }
                            $v = '<span class="wpil_ul">' . $count . '</span> ' . $v;
                            break;
                    }

                    $rep .= '</ul>';
                }

                $e_rt = esc_attr($column_name);
                $e_p_id = esc_attr($post_id);
                $delete_bar = (!empty($count)) ? 
                '<div class="update-post-links">
                    <a href="#" class="button-primary wpil-delete-selected-links disabled" style="margin: 0 0 0 10px;" data-nonce="' . wp_create_nonce(wp_get_current_user()->ID . 'delete-selected-links') . '" data-post-id="' . $post_id . '">' . __('Delete Selected', 'wpil') . '</a>
                    <div style="float: right; display: inline-block;"><strong style="margin: 0 10px 0 0;">Select All</strong><input class="wpil-select-all-dropdown-links" style="margin: 0 10px 0 0;" type="checkbox" data-post-id="' . $post_id . '"></div>
                </div>': '';

                $atts = Wpil_Toolbox::output_dropdown_wrapper_atts(array('report_type' => 'links', 'post_id' => $e_p_id, 'post_type' => $post_type, 'nonce' => wp_create_nonce(wp_get_current_user()->ID . 'wpil-collapsible-nonce')));
                $v = "<div class='wpil-collapsible-wrapper' {$atts}>
  			            <div class='wpil-collapsible wpil-collapsible-static wpil-links-count " . ((!empty($count)) ? 'wpil-collapsible-has-data': 'wpil-collapsible-no-data') . "' title='" . esc_attr($title) . "' data-wpil-report-type='$e_rt' data-wpil-report-post-id='$e_p_id'>$v</div>
  				        <div class='wpil-content'>
          			        $rep
  				        </div>
                        $delete_bar
  				    </div>";
            }

        }

        return $v;
    }

    function get_columns()
    {
        $columns = ['post_title' => __('Title', 'wpil')];
        $options = get_user_meta(get_current_user_id(), 'report_options', true);

        if (!empty($options['show_date']) && $options['show_date'] == 'on') {
            $columns['date'] = __('Published', 'wpil');
        }

        if (!empty($options['show_type']) && $options['show_type'] == 'on') {
            $columns['post_type'] = __('Type', 'wpil');
        }

        if (!empty($options['show_traffic']) && $options['show_traffic'] == 'on' && !empty(Wpil_Settings::HasGSCCredentials())) {
            $columns['organic_traffic'] = __('Organic Traffic', 'wpil');
        }

        $inbound_help_overlay = 'class="wpil-report-header-container wpil-is-tooltipped wpil-no-scale wpil-tooltip-target-parent wpil-tooltip-target.column-wpil_links_inbound_internal_count" ' . Wpil_Toolbox::generate_tooltip_text('link-report-table-inbound-internal-links-col');
        $outbound_help_overlay = 'class="wpil-report-header-container wpil-is-tooltipped wpil-no-scale wpil-tooltip-target-parent wpil-tooltip-target.column-wpil_links_outbound_internal_count" ' . Wpil_Toolbox::generate_tooltip_text('link-report-table-outbound-internal-links-col');
        $external_help_overlay = 'class="wpil-report-header-container wpil-is-tooltipped wpil-no-scale wpil-tooltip-target-parent wpil-tooltip-target.column-wpil_links_outbound_external_count" ' . Wpil_Toolbox::generate_tooltip_text('link-report-table-outbound-external-links-col');

        $inbound = '<div ' . $inbound_help_overlay . '>' . 
                        __('Inbound Internal Links', 'wpil') . 
                        '<div class="wpil-report-header-tooltip">
                            <div class="wpil_help">
                                <i class="dashicons dashicons-editor-help"></i>
                                <div class="wpil-help-text" style="display: none;">' . sprintf(__('Inbound Internal Links are links on %s on this site that are pointing to %s.', 'wpil'), '<span style="font-style: italic;float: none;">' . __('other pages', 'wpil') . '</span>', '<span style="text-decoration: underline;float: none;">' . __('this page', 'wpil') . '</span>') . '</div>
                            </div>
                        </div>
                    </div>';

        $outbound = '<div ' . $outbound_help_overlay . '>' . 
                        __('Outbound Internal Links', 'wpil') . 
                        '<div class="wpil-report-header-tooltip">
                            <div class="wpil_help">
                                <i class="dashicons dashicons-editor-help"></i>
                                <div class="wpil-help-text" style="display: none;">' . sprintf(__('Outbound Internal Links are links that are on %s and are pointing to %s on this site.', 'wpil'), '<span style="font-style: italic;float: none;">' . __('this page', 'wpil') . '</span>',  '<span style="text-decoration: underline;float: none;">' . __('other pages', 'wpil') . '</span>') . '</div>
                            </div>
                        </div>
                    </div>';

        $external = '<div ' . $external_help_overlay . '>' . 
                        __('Outbound External Links', 'wpil') . 
                        '<div class="wpil-report-header-tooltip">
                            <div class="wpil_help">
                                <i class="dashicons dashicons-editor-help"></i>
                                <div class="wpil-help-text" style="display: none;">' . sprintf(__('Outbound External Links are links that are on %s and are pointing to pages on %s.', 'wpil'),  '<span style="font-style: italic;float: none;">' . __('this page', 'wpil') . '</span>', '<span style="text-decoration: underline;float: none;">' . __('other sites', 'wpil') . '</span>') . '</div>
                            </div>
                        </div>
                    </div>';

        $columns = array_merge($columns, [
            WPIL_LINKS_INBOUND_INTERNAL_COUNT => $inbound,
            WPIL_LINKS_OUTBOUND_INTERNAL_COUNT => $outbound,
            WPIL_LINKS_OUTBOUND_EXTERNAL_COUNT => $external,
        ]);

        return $columns;
    }

    function column_post_title($item)
    {
        $post = $item['post'];

        $actions = [];

        $title = '<div class="wpil-report-row-title-container"><a href="' . esc_url($post->getLinks()->edit) . '" class="row-title">' . esc_attr($post->getTitle()) . '</a><div class="wpil-row-title-icon-container">' . $this->get_title_icons($post) . '</div></div>';
        $actions['view'] = '<a target="_blank" href="' . esc_url($post->getLinks()->view) . '">View</a>';
        $actions['edit'] = '<a target="_blank" href="' . esc_url($post->getLinks()->edit) . '">Edit / Add outbound links</a>';
        $actions['export'] = '<a target="_blank" href="' . esc_url($post->getLinks()->export) . '">Export data for support</a>';
        $actions['excel_export'] = '<a target="_blank" href="' . esc_url($post->getLinks()->excel_export) . '">Export Post Data to Excel</a>';
        $actions['refresh'] = '<a href="' . esc_url($post->getLinks()->refresh) . '">Refresh links count</a>';

        if($post->type === 'post' && !empty(EMPTY_TRASH_DAYS)){
            $name = get_post_type_labels(get_post_type_object(get_post_type($post->id)));
            $name = (!empty($name) && isset($name->singular_name)) ? $name->singular_name: 'Post';
            $actions['trash'] = '<a href="' . esc_url(get_delete_post_link($post->id)) . '" class="wpil-trash-post-link">' . sprintf(__('Trash %s', 'wpil'), $name) . '</a>';
        }

        if(isset($_GET['orphaned'])){
            $actions['ignore-orphaned'] = '<a href="#" class="wpil-ignore-orphaned-post" data-post-id="' . $post->id . '" data-type="' . $post->type . '" data-nonce="'. wp_create_nonce('ignore-orphaned-post-' . $post->id) .'">Ignore orphaned post</a>';
        }

        return $title . $this->row_actions($actions);
    }

    /**
     * Gets the icons that we'll be appending to the post titles in the report for quick information
     **/
    function get_title_icons($post){
        $icons = '';

        if($post->type === 'post'){
            $redirected_post_url = Wpil_Link::get_url_redirection($post->getViewLink());

            // if the current post has had it's URL redirected
            if(!empty($redirected_post_url)){
                // check if the redirect is pointing to a different post
                $new_post = Wpil_Post::getPostByLink($redirected_post_url);
                // if it is, or the redirect is pointing to the home url
                if(!empty($new_post) && $post->id !== $new_post->id || Wpil_Link::url_points_home($redirected_post_url)){
                    $icons .= '<div class="wpil_help">';
                    $icons .= '<i class="dashicons dashicons-hidden"></i>';
                    $icons .= '<div class="wpil-help-text" style="display: none; top: 6px; left: -81px;">' . __('Hidden by redirect', 'wpil') . '</div>';
                    $icons .= '</div>';
                }
            }
        }

        if($post->type === 'post'){
            $is_pillar = false;
            if(class_exists('WPSEO_Meta') && method_exists('WPSEO_Meta', 'get_value')){
                $is_pillar = (WPSEO_Meta::get_value('is_cornerstone', $post->id) === '1');
            }

            if(empty($is_pillar) && defined('RANK_MATH_VERSION')){
                $is_pillar = Wpil_Toolbox::check_pillar_content_status($post->id);
            }

            if(!empty($is_pillar)){
                $icons .= '<div class="wpil_help">';
                $icons .= '<i class="dashicons dashicons-media-text"></i>';
                $icons .= '<div class="wpil-help-text" style="display: none;">' . __('Pillar Content', 'wpil') . '</div>';
                $icons .= '</div>';
            }
        }

        return $icons;
    }

    function get_sortable_columns()
    {
        $cols = $this->get_columns();

        $sortable_columns = [];

        foreach ($cols as $col_k => $col_name) {
            $sortable_columns[$col_k] = [$col_k, false];
        }

        return $sortable_columns;
    }

    function prepare_items()
    {
        define('WPIL_LOADING_REPORT', true);
        $options = get_user_meta(get_current_user_id(), 'report_options', true);
        $per_page = !empty($options['per_page']) ? $options['per_page'] : 20;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $start = isset($_REQUEST['paged']) ? (int)$_REQUEST['paged'] : 0;
        $orderby = (isset($_REQUEST['orderby']) && !empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : '';
        $order = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'DESC';
        $search = (!empty($_REQUEST['s'])) ? sanitize_text_field($_REQUEST['s']) : '';
        $orphaned = !empty($_REQUEST['orphaned']);

        if(empty($start) && !isset($_REQUEST['orderby']) && !isset($_REQUEST['order'])){
            if($orphaned){
                Wpil_Telemetry::log_event('report_open_orphaned');
            }else{
                Wpil_Telemetry::log_event('report_open_links');
            }
        }

        if (empty($orderby)) {
            $saved_order = get_transient('wpil_link_report_order');
            if (!empty($saved_order)) {
                $saved_order = explode(';', $saved_order);
                if (count($saved_order) == 2) {
                    $orderby = !empty($saved_order[0]) ? $saved_order[0] : '';
                    $order = !empty($saved_order[1]) ? $saved_order[1] : 'DESC';
                }
            }
        }

        if (!empty($orderby)) {
            set_transient('wpil_link_report_order', $orderby . ';' . $order);
        }

        $data = Wpil_Report::getData($start, $orderby, $order, $search, $per_page, $orphaned);

        $total_items = $data['total_items'];
        $data = $data['data'];

        $this->items = $data;

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    /**
     * Displays the search box.
     *
     * @param string $text     The 'submit' button label.
     * @param string $input_id ID attribute value for the search input field.
     */
    public function search_box( $text, $input_id ) {
        if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
            return;
        }

        $input_id = $input_id . '-search-input';

        if ( ! empty( $_REQUEST['orderby'] ) ) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
        }
        if ( ! empty( $_REQUEST['order'] ) ) {
            echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
        }
        if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
            echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
        }
        if ( ! empty( $_REQUEST['detached'] ) ) {
            echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
        }
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
            <input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" placeholder="Keyword or URL" />
            <?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
        </p>
        <?php
    }

    function extra_tablenav( $which ) {
        if ($which == "top") {
            $post_type = !empty($_GET['post_type']) ? $_GET['post_type'] : 0;
            $cat = !empty($_GET['category']) ? $_GET['category'] : 0;
            $location = !empty($_GET['location']) ? $_GET['location'] : null;
            $filter_type = !empty($_GET['filter_type']) ? $_GET['filter_type'] : 0;
            $link_type = !empty($_GET['link_type']) ? $_GET['link_type'] : null;
            $min = !empty($_GET['link_min_count']) ? $_GET['link_min_count'] : 0;
            $max = array_key_exists('link_max_count', $_GET) ? $_GET['link_max_count'] : null;

            $post_types = get_post_types(array('public' => true));
            $post_types = array_values($post_types);
            $taxonomies = get_object_taxonomies($post_types);

            $taxes = array();
            $tax_index = array();
            foreach($post_types as $ind_post_type){
                $taxonomies = get_object_taxonomies($ind_post_type);
                if(!empty($taxonomies)){
                    foreach($taxonomies as $tax){
                        $taxo = get_taxonomy($tax);
                        if($taxo->hierarchical){
                            $taxes[] = $taxo->name;
                            $tax_index[$ind_post_type][] = array($taxo->name => array());
                        }
                    }
                }
            }

            $taxonomies2 = get_categories(array('taxonomy' => $taxes, 'hide_empty' => false));
            $options = '';

            if(!empty($taxonomies2)){
                foreach($taxonomies2 as $tax){
                    foreach($tax_index as $ind_post_type => $tax_names){
                        foreach($tax_names as $key => $tax_name){
                            if(isset($tax_name[$tax->taxonomy])){
                                $selected = $tax->cat_ID===(int)$cat?' selected':'';
                                $options .= '<option value="' . $tax->cat_ID . '" ' . $selected . ' class="wpil_filter_post_type ' . $ind_post_type . '">' . $tax->name . '</option>';
                            }
                        }
                    }
                }
            }
            ?>
            <style>
                <?php
                switch ($filter_type) {
                    case '2':
                        // do nothing to hide the inputs
                        break;
                    case '1':
                        echo '.filter-by-type{display:none;}';
                        break;
                    case '0':
                    default:
                        echo '.filter-by-count{display:none;}';
                    break;
                }
                ?>
            </style>
            <div class="alignright actions bulkactions wpil-is-tooltipped_TODO" id="wpil_links_table_filter" <?php echo Wpil_Toolbox::generate_tooltip_text('link-report-filters'); ?>>
                <select name="filter_type">
                    <option value="0" <?php selected($filter_type, '0'); ?>><?php esc_html_e('Filter by Post Type', 'wpil'); ?></option>
                    <option value="1" <?php selected($filter_type, '1'); ?>><?php esc_html_e('Filter by Link Count', 'wpil'); ?></option>
                    <option value="2" <?php selected($filter_type, '2'); ?>><?php esc_html_e('Filter by Post Type & Link Count', 'wpil'); ?></option>
                </select>
                <!--filter by post type-->
                <select name="post_type" class="filter-by-type">
                    <option value="0">All types</option>
                    <?php foreach (Wpil_Settings::getAllTypes() as $type) : ?>
                        <option value="<?=esc_attr($type)?>" <?=$type===$post_type?' selected':''?>><?=ucfirst($type)?></option>
                    <?php endforeach; ?>
                </select>
                <select name="category" class="filter-by-type">
                    <option value="0">All categories</option>
                    <?php echo $options; ?>
                </select>
                <?php if (Wpil_Settings::showAllLinks()) : ?>
                    <select name="location" class="filter-by-type">
                        <option value="0">All locations</option>
                        <?php foreach (['header', 'content', 'footer'] as $loc) : ?>
                            <option value="<?=$loc?>" <?=$loc==$location?' selected':''?>><?=$loc?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <!--/filter by post type-->
                <!--filter by link counts-->
                <select name="link_type" class="filter-by-count">
                    <option value="inbound-internal"  <?php selected($link_type, 'inbound-internal'); ?>><?php esc_html_e('Inbound Internal Links', 'wpil'); ?></option>
                    <option value="outbound-internal" <?php selected($link_type, 'outbound-internal'); ?>><?php esc_html_e('Outbound Internal Links', 'wpil'); ?></option>
                    <option value="outbound-external" <?php selected($link_type, 'outbound-external'); ?>><?php esc_html_e('Outbound External Links', 'wpil'); ?></option>
                </select>
                <label for="wpil_link_min_count" class="filter-by-count">Min</label>
                <input id="wpil_link_min_count"type="number" name="link_min_count" class="filter-by-count" min="0" value="<?php echo $min; ?>" style="max-width: 70px;">
                <label for="wpil_link_max_count" class="filter-by-count">Max</label>
                <input id="wpil_link_max_count" type="number" name="link_max_count" class="filter-by-count" min="0" <?php if(null !== $max){ echo 'value="' . $max . '"';} ?> style="max-width: 70px;">
                <!--/filter by link counts-->
                <span class="button-primary">Filter</span>
                <input type="hidden" class="post-filter-nonce" value="<?php echo wp_create_nonce(get_current_user_id() . 'wpil_filter_nonce'); ?>">
            </div>
            <?php
        }
    }
}
