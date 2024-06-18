<?php
/* 
  Plugin Name: Version Reporter
  Plugin URI: https://chankov.net/
  Description: Allow to provide wordpress version for collector website
  Version: 1.1
  Author: Nik Chankov
  Author URI: https://chankov.net
  License: GPLv2 or later
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if (!defined('WPINC')) {
    die();
}
/**
 * Plugin's actions
 */
add_action('init', function () {
    global $wp;

    // Store Security Token
    $token = get_option('version_reporter_token');
    if (isset($_POST['version_reporter_token']) && $_POST['version_reporter_token']) {
        //rewrite the settings, so if empty posted it wouldn't change
        if (
            isset($_POST['version_reporter_token']) &&
            $_POST['version_reporter_token'] &&
            mb_strlen($_POST['version_reporter_token']) >= 10
        ) {
            if ($token) {
                update_option('version_reporter_token', $_POST['version_reporter_token']);
            } else {
                add_option('version_reporter_token', $_POST['version_reporter_token']);
            }
            $token = $_POST['version_reporter_token'];
        }
    }
    $websites = get_option('version_reporter_websites');
    //Remove website
    if (isset($_POST['version_reporter_remove']) && $_POST['version_reporter_remove']) {
        $urls = array_column($websites, 'url');
        $index = array_search(trim($_POST['version_reporter_remove']), $urls);
        unset($websites[$index]);
        $websites = array_values($websites);
        update_option('version_reporter_websites', $websites);
        die('Record has been removed');
    }
    //Add website(s)
    if (isset($_POST['version_reporter_website']) && $_POST['version_reporter_website']) {
        $new_urls = explode("\n", $_POST['version_reporter_website']);
        if (is_array($websites)) {
            $urls = array_column($websites, 'url');
            foreach ($new_urls as $url) {
                if (!in_array(trim($url), $urls) && filter_var(trim($url), FILTER_VALIDATE_URL)) {
                    $websites[] = ['url' => trim($url)];
                }
            }
            update_option('version_reporter_websites', $websites);
        } else {
            $websites = [];
            $urls = [];
            foreach ($new_urls as $url) {
                if (!in_array(trim($url), $urls) && filter_var(trim($url), FILTER_VALIDATE_URL)) {
                    $websites[] = ['url' => trim($url)];
                }
            }
            add_option('version_reporter_websites', $websites);
        }
    }
    $wp->parse_request();
    $current_url = mb_substr(home_url($wp->request), mb_strlen(home_url()));
    //Report current wordpress version
    if (preg_match('/^\/version-reporter.json\/?$/', $current_url)) {
        if (isset($_REQUEST['token']) && $_REQUEST['token']) {
            $token = $_REQUEST['token'];
        } else {
            $headers = apache_request_headers();
            if (isset($headers['x-version-reporter'])) {
                $token = $headers['x-version-reporter'];
            }
        }
        $tok = get_option('version_reporter_token');
        header('Content-type: application/json');
        header("Content-Disposition: inline; filename=ajax.json");
        if (isset($token) && $token && $token === $tok) {
            echo json_encode(['version' => get_bloginfo('version')]);
        } else {
            echo json_encode(['error' => 'Unauthorized']);
        }
        die();
    }
    //
    //Check the version of a particular website
    if (preg_match('/^\/version-check\/?$/', $current_url)) {
        header('Content-type: application/json');
        header("Content-Disposition: inline; filename=ajax.json");
        if (!current_user_can('administrator')) {
            die('Wrong priviledges');
        }
        $token = get_option('version_reporter_token');
        if (!isset($_POST['url']) || !$_POST['url']) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            die();
        }

        $curl = curl_init($_POST['url'] . '/version-reporter.json');
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'x-version-reporter: ' . $token,
            'Content-Type: application/json'
        ]);
        $result = curl_exec($curl); //This is the result from Txtlocal
        curl_close($curl);
        if ($result) {
            try {
                $result = json_decode($result);
                if (isset($result->version) && $result->version) {
                    echo json_encode(['success' => true, 'version' => $result->version]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Wrong response']);
                }
            } catch (Exception $ex) {
                echo json_encode(['success' => false, 'error' => 'Wrong response']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => "Can't access the url"]);
        }
        die();
    }
});

add_filter('plugin_action_links_version_reporter/plugin.php', function ($links) {
    // Build and escape the URL.
    $url = esc_url(
        add_query_arg(
            'page',
            'version-reporter-config',
            get_admin_url() . 'admin.php'
        )
    );
    // Create the link.
    $settings_link = "<a href='$url'>" . __('Settings') . '</a>';
    // Adds the link to the end of the array.
    array_push(
        $links,
        $settings_link
    );
    return $links;
});

/**
 * Add the admin menu of the plugin
 */
add_action('admin_menu', function () {
    add_menu_page(
        'Version Reporter',
        'Version Reporter',
        'manage_options',
        'version-reporter-config',
        function () {
            include_once (plugin_dir_path(__FILE__) . '/config.php');
        },
        'dashicons-menu',
        150
    );
});