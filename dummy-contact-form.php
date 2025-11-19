<?php
/*
Plugin Name: Dummy Contact Form
Plugin URI: https://example.com/dummy-contact-form
Description: A simple dummy contact form plugin for testing purposes.
Version: 7.0.0
Author: Tahasin
Author URI: https://w3eden.com
License: GPL2
Text Domain: dummy-contact-form
Domain Path: /languages
*/
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// echo 'hello';


class Dummy_Contact_Form
{

    public function __construct()
    {
        add_action('init', array($this, 'create_custom_post_type'));
        add_action('admin_menu', array($this, 'add_custom_submenus'));
        add_action('wp_enqueue_scripts', array($this, 'loadAssets'));


        add_action('wp_footer', array($this, 'loadScripts'));

        add_shortcode('dummy_contact_form', array($this, 'render_contact_form'));


        //register rest api
        add_action('rest_api_init', array($this, 'register_rest_api'));
    }

    public function create_custom_post_type()
    {
        $args = array(
                'public' => true,
                'has_archive' => true,
            // 'show_ui' => true,
                'supports' => array('title'),
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'labels' => array(
                        'name' => 'Dummy Contacts',
                        'singular_name' => 'Dummy Contact'
                ),
                'menu_icon' => 'dashicons-grid-view'
        );
        register_post_type('dummy_contact', $args);
    }

    public function add_custom_submenus()
    {
        // Parent slug = edit.php?post_type=your_cpt
        $parent_slug = 'edit.php?post_type=dummy_contact';

        add_submenu_page(
                $parent_slug,                 // parent menu
                'Settings',                   // page title
                'Settings',                   // submenu label
                'manage_options',             // capability
                'dummy-contact-settings',     // slug
                array($this, 'settings_page') // callback
        );

        add_submenu_page(
                $parent_slug,
                'Reports',
                'Reports',
                'manage_options',
                'dummy-contact-reports',
                array($this, 'reports_page')
        );
    }

    public function settings_page()
    {
        echo '<div class="wrap"><h1>Dummy Contact Settings</h1><p>Settings content goes here.</p></div>';
    }

    public function reports_page()
    {
        echo '<div class="wrap"><h1>Dummy Contact Reports</h1><p>Reports content goes here.</p></div>';
    }

    public function loadAssets()
    {
        wp_enqueue_style(
                'dummy-contact-form-style',
                plugin_dir_url(__FILE__) . 'css/dummy-contact-form.css',
                array(),
                '1.0.0',
                'all'
        );

        wp_enqueue_script(
                'dummy-contact-form-script',
                plugin_dir_url(__FILE__) . 'js/dummy-contact-form.js',
                array('jquery'),
                '1.0.0',
                true
        );
    }


    public function render_contact_form()
    {
        // ob_start();
        ?>
        <form id="dummy-contact-form" method="post" action="">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>


            <label for="phone">Phone:</label>
            <input type="tel" id="phone" name="phone" required>

            <label for="message">Message:</label>
            <textarea id="message" name="message" required></textarea>

            <button type="submit" title="Submit" name="submit_dummy_contact_form" value="Send">
        </form>
        <?php
        // if (isset($_POST['submit_dummy_contact_form'])) {
        //     $this->handle_form_submission();
        // }
        // return ob_get_clean();
    }

    function register_rest_api()
    {
        register_rest_route('dummy-contact-form/v1', '/submit', array(
                'methods' => 'POST',
                'callback' => array($this, 'handle_form_submission'),

        ));
    }

    public function handle_form_submission($request)
    {
        // echo 'this endpoint is working';

        $headers = $request->get_headers();
        $params = $request->get_params();
        $nonce = $headers['x_wp_nonce'][0] ?? '';

        // if (wp_verify_nonce($nonce, 'wp_rest')) {
        //     echo 'Nonce is valid';
        // } else {
        //     return new WP_REST_Response('Message not sent. Nonce is invalid', 403);
        // }

        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_REST_Response('Message not sent. Nonce is invalid', 403);
        }


        $post_id = wp_insert_post(array(
                'post_type' => 'dummy_contact',
                'post_title' => sanitize_text_field($params['name']),
                'post_content' => sanitize_textarea_field($params['message']),
                'post_status' => 'publish',
                'meta_input' => array(
                        'email' => sanitize_email($params['email']),
                        'phone' => sanitize_text_field($params['phone'])
                )
        ));

        if ($post_id) {
            return new WP_REST_Response('Message sent successfully', 200);
        }

        // echo json_encode($headers);


        // $params = $request->get_params();

        // // Sanitize and validate input data
        // $name = sanitize_text_field($params['name']);
        // $email = sanitize_email($params['email']);
        // $phone = sanitize_text_field($params['phone']);
        // $message = sanitize_textarea_field($params['message']);

        // // Create a new Dummy Contact post
        // $post_id = wp_insert_post(array(
        //     'post_type' => 'dummy_contact',
        //     'post_title' => $name,
        //     'post_content' => $message,
        //     'post_status' => 'publish',
        //     'meta_input' => array(
        //         'email' => $email,
        //         'phone' => $phone
        //     )
        // ));

        // if (is_wp_error($post_id)) {
        //     return new WP_Error('form_submission_failed', 'Failed to submit the form.', array('status' => 500));
        // }

        // return array('success' => true, 'message' => 'Form submitted successfully!');
    }


    public function loadScripts()
    {
        // ob_start();
        ?>
        <script>
            // (function($) {
            //     console.log('wow');
            // })(jQuery);


            (function ($) {
                $('#dummy-contact-form').submit(function (e) {

                    var nonce = '<?php echo wp_create_nonce("wp_rest"); ?>';


                    e.preventDefault();
                    var form = $(this).serialize(); // jQuery form object

                    console.log('nice');
                    alert('Form submitted!');

                    $.ajax({
                        method: 'POST',
                        url: '<?php echo rest_url("dummy-contact-form/v1/submit"); ?>',
                        headers: {
                            'X-WP-Nonce': nonce
                        },
                        data: form
                    });
                });
            })(jQuery);
        </script>

        <?php

    }
}

new Dummy_Contact_Form();
