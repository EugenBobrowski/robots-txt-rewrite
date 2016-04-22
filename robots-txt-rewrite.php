<?php
/**
 * @package Robots.txt rewriter
 * @version 1.6
 */
/*
Plugin Name: Robots.txt rewrite
Plugin URI: http://wordpress.org/plugins/robots-txt-rewriter/
Description: This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.
Author: Eugen Bobrowski
Version: 1.0
Author URI: http://atf.li/
*/


class RobotsTxtRewrite
{

    protected static $instance;

    private function __construct()
    {
        add_action('admin_menu', array($this, 'options_page'));
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


            $disallow = "Disallow: $path/wp-includes/\n";
            $disallow .= "Disallow: $path/wp-content/plugins/\n";
            $disallow .= "Disallow: $path/wp-content/cache/\n";
            $disallow .= "Disallow: $path/wp-content/themes/\n";
            $disallow .= "Disallow: $path/*?*\n";

            $pos = strpos($output, 'Disallow:');
            $output = ($pos !== false) ? substr_replace($output, $disallow, $pos, 0) : $output;

            $output .= "\nHost: " . get_site_url() . "\n";
        }

        return $output;
    }

    public function options_page()
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

        if (isset($_POST['robots_options'])) {
            $to_save = array();
            update_option('si_options', $to_save);
        }
    }

    public function options_page_callback()
    {
        $options = wp_parse_args(get_option('robots_options'), array(
            'blog_public' => 0,
            'allowed' => array(
                array(
                    'allowed' => 0,
                    'path' => '/wp-admin/',
                ),
                array(
                    'allowed' => 1,
                    'path' => '/wp-admin/admin-ajax.php',
                )
            )
        ));


        ?>
        <div class="wrap atf-fields">

            <h2><?php echo esc_html(get_admin_page_title()); ?></h2>

            <form method="post">

                <table class="form-table">
                    <tr class="form-field form-required">
                        <th scope="row"><label>Search Engine Visibility</label></th>
                        <td><?php AtfHtmlHelper::tumbler(array(
                                'id' => 'seo_visible',
                                'value' => $options['blog_public'],

                            )); ?></td>
                    </tr>
                    <tr class="form-required">
                        <td colspan="2">
                            <?php AtfHtmlHelper::group(array(
                                    'name' => 'allowed',
                                    'items' => array(

                                        'path' => array(
                                            'title' => __('Path', 'robots-txt-rewrite'),
                                            'type' => 'text',
                                            'desc' => 'Relative path',

                                        ),
                                        'allowed' => array(
                                            'title' => 'Allow',
                                            'type' => 'tumbler',
                                            'options' => array('plain' => 'Text', 'html' => 'HTML'),
                                            'desc' => 'Allow / Disallow',
                                            'cell_style' => 'text-align: center;',
                                        ),

                                    ),
                                    'value' => $options['allowed'],
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
