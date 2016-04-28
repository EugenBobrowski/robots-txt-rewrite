<?php
/**
 * @package Robots.txt rewriter
 * @version 1.6
 */
/*
Plugin Name: Robots.txt rewrite
Plugin URI: http://wordpress.org/plugins/robots-txt-rewriter/
Description: Manage your robots.txt form admin side. A simple plugin to manage your robots.txt WordPress returning. Plugin provide to discourage search engines from indexing site. Use the stock WordPress option.
Author: Eugen Bobrowski
Version: 1.0
Author URI: http://atf.li/
*/


class RobotsTxtRewrite
{

    protected static $instance;

    private function __construct()
    {
        add_action('admin_menu', array($this, 'menu_item'));
        add_filter('robots_txt', array($this, 'robots_txt_edit'), 10, 2);
    }

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;

    }

    public function robots_txt_edit($output, $public)
    {
        //do_robots();

        if ('0' != $public) {
            $site_url = parse_url(site_url());
            $path = (!empty($site_url['path'])) ? $site_url['path'] : '';

            $options = $this->get_options();

            $allows = '';

            foreach ($options['allows'] as $allow) {
                if ($allow['allowed']) {
                    $allows .= "Allow: {$path}{$allow['path']}\n";
                } else {
                    $allows .= "Disallow: {$path}{$allow['path']}\n";
                }
            }
            $output = "User-agent: *\n";
            $output .= $allows;

            $output .= "\nHost: " . get_site_url() . "\n";
        }

        return $output;
    }
    public function get_options () {
        $options = wp_parse_args(get_option('robots_options'), array(
            'blog_public' => get_option( 'blog_public' ),
            //default demo paths
            'allows' => array(
                array(
                    'allowed' => 1,
                    'path' => '/',
                ),
                array(
                    'allowed' => 0,
                    'path' => '/wp-admin/',
                ),
                array(
                    'allowed' => 0,
                    'path' => '/wp-includes/',
                ),
                array(
                    'allowed' => 0,
                    'path' => '/wp-content/plugins/',
                ),
                array(
                    'allowed' => 0,
                    'path' => '/wp-content/cache/',
                ),
                array(
                    'allowed' => 0,
                    'path' => '/wp-content/themes/',
                ),
                array(
                    'allowed' => 1,
                    'path' => '/wp-admin/admin-ajax.php',
                ),

            )
        ));
        return $options;
    }

    public function menu_item()
    {
        $hook_suffix = add_options_page(
            'Robots.txt Options',
            'Robots.txt Options',
            'manage_options',
            'robots-txt-options',
            array($this, 'options_page_callback')
        );
        global $plugin_page;

        if (strpos($hook_suffix, $plugin_page)) {
            include_once plugin_dir_path(__FILE__) . 'atf-html-helper/htmlhelper.php';
            add_action('admin_enqueue_scripts', array('AtfHtmlHelper', 'assets'));
            $this->save_options();
        }

    }

    public function save_options()
    {
        if (
            isset($_POST['robots_options']) &&
            (! isset( $_POST['robots_txt_rewrite_options_nonce_field'] )
                || ! wp_verify_nonce( $_POST['robots_txt_rewrite_options_nonce_field'], 'save_options_robots_txt_rewrite' ) ) ) {
            print 'Sorry, your nonce did not verify.';
            exit;
        }

        if (isset($_POST['blog_public'])) {

            update_option('blog_public', sanitize_option('blog_public', $_POST['blog_public']));
        }
        if (isset($_POST['robots_options'])) {
            
            $to_save = array();
            
            foreach ($_POST['robots_options']['allows'] as $allows) {
                
                if (!isset($allows['path']) || !isset($allows['allowed'])) continue;
                
                $to_save['allows'][] = array(
                    'path' => sanitize_text_field($allows['path']),
                    'allowed' => intval($allows['allowed']),
                );
            }

            update_option('robots_options', $to_save);
        }



    }

    public function options_page_callback()
    {
        $options = $this->get_options();


        ?>
        <div class="wrap atf-fields">

            <h2><?php echo esc_html(get_admin_page_title()); ?>
                <a href="<?php echo site_url('/robots.txt')?>"
                class="page-title-action">robots.txt</a>
            </h2>
            <?php 
            ?>

            <form method="post">
                <?php wp_nonce_field( 'save_options_robots_txt_rewrite', 'robots_txt_rewrite_options_nonce_field' ); ?>

                <table class="form-table">
                    <tr class="form-field form-required">
                        <th scope="row"><label><?php _e('Search Engine Visibility'); ?></label></th>
                        <td><?php AtfHtmlHelper::tumbler(array(
                                'id' => 'blog_public',
                                'value' => $options['blog_public'],

                            )); ?></td>
                    </tr>
                    <tr class="form-required">
                        <td colspan="2">
                            <?php AtfHtmlHelper::group(array(
                                    'name' => 'robots_options[allows]',
                                    'items' => array(

                                        'path' => array(
                                            'title' => __('Path', 'robots-txt-rewrite'),
                                            'type' => 'text',
                                            'desc' => __('Relative path', 'robots-txt-rewrite'),

                                        ),
                                        'allowed' => array(
                                            'title' => __('Allow', 'robots-txt-rewrite'),
                                            'type' => 'tumbler',
                                            'options' => array('plain' => 'Text', 'html' => 'HTML'),
                                            'desc' => __('Allow / Disallow', 'robots-txt-rewrite'),
                                            'cell_style' => 'text-align: center;',
                                        ),

                                    ),
                                    'value' => $options['allows'],
                                )
                            );
                            ?>
                        </td>
                    </tr>
                </table>

                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary"
                                         value="Submit"></p>

            </form>
        </div>
        <?php
    }

}

RobotsTxtRewrite::get_instance();
