<?php

/**
 * Plugin Name
 *
 * @package           WPFormsWebhookTemplates
 * @author            Oshane Bailey
 *
 * @wordpress-plugin
 * Plugin Name:       WPForms Webhook Templates
 * Plugin URI:        https://github.com/JamDevCo/wpforms-webhook-templates
 * Description:       Webhook Templates for WPForms
 * Version:           0.0.1
 * Author:            Oshane Bailey
 * Author URI:        https://github.com/b4oshany
 * Text Domain:       wpforms-webhook-templates
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

 /**
 * Show values in Dropdown, checkboxes and Multiple Choice.
 *
 * @link https://wpforms.com/developers/add-field-values-for-dropdown-checkboxes-and-multiple-choice-fields/
 */

define('WPF_WH_TEMPLATES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPF_WH_TEMPLATES_PLUGIN_PUBLIC_URL', WPF_WH_TEMPLATES_PLUGIN_URL . 'public' . DIRECTORY_SEPARATOR);
define('WPF_WH_TEMPLATES_PLUGIN_PUBLIC_IMG_URL', WPF_WH_TEMPLATES_PLUGIN_PUBLIC_URL . 'img'  . DIRECTORY_SEPARATOR);

define('WPF_WH_TEMPLATES_PLUGIN_PATH', plugin_dir_path(__FILE__));

define('WPF_WH_TEMPLATE_LIST', [
    'api.telegram.org' => 'wpf_telegram_webhook'
]);

   
add_action( 'wpforms_fields_show_options_setting', '__return_true' );
/**
 * Send field values in Dropdown, checkboxes and Multiple Choice through webhook.
 *
 * @link https://wpforms.com/developers/how-to-send-field-values-with-webhooks/
 */

 /**
  * Output to json
  *
  * @param string $filename
  * @param array $data
  * @return void
  */
 function output_to_json($filename, $data) {
    $jd = json_encode($data);
    file_put_contents(WPF_WH_TEMPLATES_PLUGIN_PATH."$filename.json", $jd);
 }

 /**
  * Associative array to string
  *
  * @param string $glue
  * @param array $array
  * @param array $excludes
  * @return string
  */
 function implode_assoc(array $array, string $glue=', ', array $excludes = []) {
    $flattened = $array;

    foreach( $excludes as $exclude ) {
        unset($flattened[$exclude]);
    }

    array_walk($flattened, function(&$value, $key) {
        $value = "{$key}: {$value}";
    });
    return implode($glue, $flattened);
 }

 function getArrayValue(array $array, $key, $default='') {
    return ( !empty($array[$key]) ) ? $array[$key] : $default;
}


 /**
  * Process Webhook
  * @see https://telegram-bot-sdk.readme.io/reference/sendmessage
  *
  * @param mixed $options
  * @param mixed $webhook_data
  * @param mixed $fields
  * @param mixed $form_data
  * @param mixed $entry_id
  * @return array
  */
  function wpf_telegram_webhook($options, $webhook_data, $fields, $form_data, $entry_id) {

    $form_title = getArrayValue($form_data['settings'], 'form_title', 'Website Form');
    $webhook_name = getArrayValue($webhook_data, 'name', 'Webhook');

    $site_url = get_site_url();
    $site_title = get_bloginfo('name');

    $body = ! is_array( $options[ 'body' ] ) ? json_decode( $options[ 'body' ], true ) : $options[ 'body' ];

    $tg_message = implode_assoc($body, " \n ", ['chat_id']);
    $body['text'] = "$tg_message"
        ."\n\n -- Sent from $site_title Website. --"
        ."\n $webhook_name for $form_title form \n"
        .$site_url;
 
    // Format request data.
    if (
        ! empty( $options[ 'method' ] ) &&
        $options[ 'method' ] !== 'GET' &&
        $webhook_data[ 'format' ] === 'json'
    ) { 
        $options[ 'body' ] = wp_json_encode( $body );
    }
    return $options;

  }

 /**
  * Process Webhook
  * @see https://telegram-bot-sdk.readme.io/reference/sendmessage
  *
  * @param mixed $options
  * @param mixed $webhook_data
  * @param mixed $fields
  * @param mixed $form_data
  * @param mixed $entry_id
  * @return array
  */
function wpf_webhook_templates_process_delivery_request_options($options, $webhook_data, $fields, $form_data, $entry_id) {
    if ( ! wpforms_show_fields_options_setting() ) {
        return $options;
    }

    $webhook_url = getArrayValue($webhook_data, 'url');
    $webhook_domain = parse_url($webhook_url, PHP_URL_HOST);
    $webhook_call = getArrayValue(WPF_WH_TEMPLATE_LIST, strtolower($webhook_domain), null);

    if ( empty($webhook_call) || ! function_exists($webhook_call) ) {
       return $options; 
    }

    if (
        empty( $options[ 'body' ] ) ||
        empty( $webhook_data[ 'body' ] ) ||
        empty( $fields )
    ) {
        return $options;
    }

    try {
        return $webhook_call(
            $options, $webhook_data, $fields, $form_data, $entry_id
        );
    } catch (\Throwable $th) {
        return $options;
    }
 
}

add_filter( 
    'wpforms_webhooks_process_delivery_request_options',
    'wpf_webhook_templates_process_delivery_request_options',
    10,
    5
);



?>