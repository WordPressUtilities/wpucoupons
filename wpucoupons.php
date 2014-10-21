<?php

/*
Plugin Name: WPU Coupons
Plugin URI:
Description: This plugin let you create coupons
Version: 0.1
*/

class WPUCoupons
{
    public $post_type = 'codes_promo';
    private $nonce_name = 'wpucoupon_noncename';
    private $nonce_id = 'wpucoupon_nonceid';
    private $fields = array(
        'coupon_name' => array(
            'label' => 'Nom public du coupon',
            'placeholder' => 'Ex: Frais de ports offerts',
        ) ,
        'coupon_code' => array(
            'label' => 'Code coupon',
            'islogin' => 1,
            'placeholder' => 'Ex: tcx123'
        ) ,
        'coupon_price_value' => array(
            'label' => 'Montant facultatif - En euros sans cents',
            'isnumeric' => 1,
            'placeholder' => 'Ex: 100'
        )
    );

    function __construct() {
        add_action('init', array(&$this,
            'create_posttype'
        ));
        if (is_admin()) {
            add_action('add_meta_boxes', array(&$this,
                'add_metabox'
            ));
            add_action('save_post', array(&$this,
                'metabox_callback'
            ));
        }
    }

    /* ----------------------------------------------------------
      Coupon methods
    ---------------------------------------------------------- */

    public function get_coupon($coupon_code = '') {
        $coupon_code = strtolower($coupon_code);
        $return = false;
        $wpq_get_coupon = new WP_Query(array(
            'posts_per_page' => 1,
            'post_type' => $this->post_type,
            'meta_key' => 'coupon_code',
            'meta_query' => array(
                array(
                    'key' => 'coupon_code',
                    'value' => $coupon_code,
                ) ,
            )
        ));
        if ($wpq_get_coupon->have_posts()) {
            $wpq_get_coupon->the_post();
            $return = array();
            foreach ($this->fields as $id => $field) {
                $val = get_post_meta(get_the_ID() , $id, 1);;
                if (isset($field['isnumeric']) && $field['isnumeric']) {
                    $val = intval($val, 10);
                }
                $return[$id] = $val;
            }
        }
        wp_reset_postdata();
        return $return;
    }

    /* ----------------------------------------------------------
      WordPress settings
    ---------------------------------------------------------- */

    public function create_posttype() {
        register_post_type($this->post_type, array(
            'labels' => array(
                'name' => __('Codes promo') ,
                'singular_name' => __('Code promo')
            ) ,
            'supports' => array(
                'title'
            ) ,
            'menu_icon' => 'dashicons-exerpt-view',
            'public' => true,
            'has_archive' => true,
        ));
    }

    public function add_metabox() {
        add_meta_box('wpucoupon_metabox', __('Conditions du code promo') , array(&$this,
            'metabox_content'
        ) , $this->post_type);
    }

    public function metabox_content($post) {
        wp_nonce_field($this->nonce_name, $this->nonce_id);
        foreach ($this->fields as $id => $field) {
            $value = get_post_meta($post->ID, $id, 1);
            echo '<p>' . '<label><strong>' . $field['label'] . '</strong></label><br />' . '<input placeholder="' . $field['placeholder'] . '" type="text" name="' . $id . '" value="' . esc_attr($value) . '" /><br />' . '</p>';
        }
    }

    public function metabox_callback($post_id) {

        if (!class_exists('WPUValidateForm')) {
            exit('WPUValidateForm is not enabled.');
        }

        // Check security
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !isset($_POST[$this->nonce_id]) || !wp_verify_nonce($_POST[$this->nonce_id], $this->nonce_name) || (isset($_POST['post_type']) && $_POST['post_type'] != $this->post_type)) {
            return;
        }

        // Check the user's permissions.
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }

        // Check form values
        $form = new WPUValidateForm();
        $valid = $form->validate_values_from($this->fields, $_POST);
        if ($valid['has_errors']) {
            return;
        }
        foreach ($valid['values'] as $meta_key => $meta_value) {
            update_post_meta($post_id, $meta_key, $meta_value);
        }
    }
}

$WPUCoupons = new WPUCoupons();
