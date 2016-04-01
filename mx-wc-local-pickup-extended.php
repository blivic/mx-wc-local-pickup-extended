<?php
/*
Plugin Name: MX Woocommerce Local Pickup Extended
Plugin URI: http://media-x.hr
Description: Plugin for extending local pickup options
Text Domain:: mx-wc-local-pickup-extended
Domain Path: /languages/
Version: 1.0
Author: Media X
Author URI: http://media-x.hr
License: GPLv3
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('MXWC_Shipping_Options')) {
	
	load_plugin_textdomain( 'mx-wc-local-pickup-extended', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    function mx_shipping_methods_init() {

        class MXWC_Shipping_Options extends WC_Shipping_Method {

            public function __construct() {
                $this->id = 'mx_local_shipping';
                $this->method_title = __( 'Local Pickup Extended', 'mx-wc-local-pickup-extended' );
                $this->title = __('Local Pickup Extended' , 'mx-wc-local-pickup-extended');
                $this->options_array_label = 'mx_shipping_options';
                $this->method_description = __('Local pickup with user selectable options' , 'mx-wc-local-pickup-extended' );
                add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_shipping_options' ) );
                $this->init();
            }

            /*
             * Init settings
             */
            function init() {
                // Load the settings API
                $this->init_form_fields();
                $this->init_settings();

                // Define user set variables
		        $this->title        = $this->get_option( 'title' );
		        $this->type         = $this->get_option( 'type' );
		        $this->fee          = $this->get_option( 'fee' );
		        $this->type         = $this->get_option( 'type' );
		        $this->codes        = $this->get_option( 'codes' );
		        $this->availability = $this->get_option( 'availability' );
		        $this->countries    = $this->get_option( 'countries' );

                $this->get_shipping_options();

                add_filter( 'woocommerce_shipping_methods', array(&$this, 'add_mx_shipping_methods'));
                add_action('woocommerce_cart_totals_after_shipping', array(&$this, 'mx_review_order_shipping_options'));
                add_action('woocommerce_review_order_after_shipping', array(&$this, 'mx_review_order_shipping_options'));
                add_action( 'woocommerce_checkout_update_order_meta', array(&$this, 'mx_field_update_shipping_order_meta'), 10, 2);
				
				if( ! is_admin() ) {
                    add_action( 'woocommerce_cart_shipping_method_full_label', array(&$this, 'negative_pickup_label'), 10, 2);
                }

                if (is_admin()) {
                    add_action( 'woocommerce_admin_order_data_after_shipping_address', array(&$this, 'mx_display_shipping_admin_order_meta'), 10, 2 );
                }
            }

            /*
             * calculate_shipping function
             */
           function calculate_shipping($package = array()) {
                $shipping_total = 0;
                $fee = ( trim($this->fee) == '' ) ? 0 : $this->fee;

                if ($this->type == 'fixed')
                    $shipping_total = $this->fee;

                if ($this->type == 'percent')
                    $shipping_total = $package['contents_cost'] * ( $this->fee / 100 );

                if ($this->type == 'product') {
                    foreach ($package['contents'] as $item_id => $values) {
                        $_product = $values['data'];

                        if ($values['quantity'] > 0 && $_product->needs_shipping()) {
                            $shipping_total += $this->fee * $values['quantity'];
                        }
                    }
                }

                $rate = array(
                    'id' => $this->id,
                    'label' => $this->title,
                    'cost' => $shipping_total
                );

                $this->add_rate($rate);
            }

            /*
             * init_form_fields function
             */
            function init_form_fields() {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('Enable', 'woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Enable Local Pickup Extended ', 'mx-wc-local-pickup-extended'),
                        'default' => 'no'
                    ),
                   'title' => array(
                        'title' => __('Title', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('This controls the title which the user sees during checkout.', 'mx-wc-local-pickup-extended'),
                        'default' => __('Local Pickup Extended', 'mx-wc-local-pickup-extended'),
                        'desc_tip' => true,
                    ),
					'type' => array(
                        'title' => __('Fee/Discount Type', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('How to calculate pickup charges', 'mx-wc-local-pickup-extended'),
                        'default' => 'fixed',
                        'options' => array(
                            'fixed' => __('Fixed amount', 'woocommerce'),
                            'percent' => __('Percentage of cart total', 'woocommerce'),
                            'product' => __('Fixed amount per product', 'woocommerce'),
                        ),
                        'desc_tip' => true,
                    ),
                    'fee' => array(
                        'title' => __('Pickup Fee/Discount', 'mx-wc-local-pickup-extended'),
                        'type' => 'price',
                        'description' => __('What fee do you want to charge for local pickup? Enter negative value for discount. Leave blank to disable.', 'mx-wc-local-pickup-extended'),
                        'default' => '',
                        'desc_tip' => true,
                        'placeholder' => wc_format_localized_price(0)
                    ),
                    'shipping_options_table' => array(
                        'type' => 'shipping_options_table'
                    ),
                    'codes' => array(
                        'title' => __('Zip/Post Codes', 'mx-wc-local-pickup-extended'),
                        'type' => 'textarea',
                        'description' => __('What zip/post codes can use the local pickup option? Separate codes with a comma. Accepts wildcards, e.g. P* will match a postcode of PE30.', 'mx-wc-local-pickup-extended'),
                        'default' => '',
                        'desc_tip' => true,
                        'placeholder' => '12345, 56789, etc'
                    ),
                    'availability' => array(
                        'title' => __('Method availability', 'woocommerce'),
                        'type' => 'select',
                        'default' => 'all',
                        'class' => 'availability',
                        'options' => array(
                            'all' => __('All allowed countries', 'woocommerce'),
                            'specific' => __('Specific Countries', 'woocommerce')
                        )
                    ),
                    'countries' => array(
                        'title' => __('Specific Countries', 'woocommerce'),
                        'type' => 'multiselect',
                        'class' => 'chosen_select',
                        'css' => 'width: 450px;',
                        'default' => '',
                        'options' => WC()->countries->get_shipping_countries(),
                        'custom_attributes' => array(
                            'data-placeholder' => __('Select some countries', 'woocommerce')
                        )
                    )
                );
            }

            /*
             * admin_options function
             */
           function admin_options() {
                   ?>
                   <h3><?php echo $this->method_title; ?></h3>
                   <p><?php _e( 'Local pickup extended is a shipping method for picking up orders locally, on multiple locations.', 'mx-wc-local-pickup-extended' ); ?></p>
                   <table class="form-table">
                           <?php $this->generate_settings_html(); ?>
                   </table> <?php
           }

            /*
             * is_available function
             */
            function is_available($package) {

                if ($this->enabled == "no")
                    return false;

                // If post codes are listed, use them
                $codes = '';
                if ($this->codes != '') {
                    foreach (explode(',', $this->codes) as $code) {
                        $codes[] = $this->clean($code);
                    }
                }

                if (is_array($codes)) {

                    $found_match = false;

                    if (in_array($this->clean($package['destination']['postcode']), $codes)) {
                        $found_match = true;
                    }


                    // Pattern match
                    if (!$found_match) {

                        $customer_postcode = $this->clean($package['destination']['postcode']);
                        foreach ($codes as $c) {
                            $pattern = '/^' . str_replace('_', '[0-9a-zA-Z]', $c) . '$/i';
                            if (preg_match($pattern, $customer_postcode)) {
                                $found_match = true;
                                break;
                            }
                        }
                    }


                    // Wildcard search
                    if (!$found_match) {

                        $customer_postcode = $this->clean($package['destination']['postcode']);
                        $customer_postcode_length = strlen($customer_postcode);

                        for ($i = 0; $i <= $customer_postcode_length; $i++) {

                            if (in_array($customer_postcode, $codes)) {
                                $found_match = true;
                            }

                            $customer_postcode = substr($customer_postcode, 0, -2) . '*';
                        }
                    }

                    if (!$found_match) {
                        return false;
                    }
                }

                if ($this->availability == 'specific') {
                    $ship_to_countries = $this->countries;
                } else {
                    $ship_to_countries = array_keys(WC()->countries->get_shipping_countries());
                }

                if (is_array($ship_to_countries)) {
                    if (!in_array($package['destination']['country'], $ship_to_countries)) {
                        return false;
                    }
                }

                // Great, we passed! Let's proceed
                return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', true, $package);
            }

            /*
             * clean function
             */
            function clean($code) {
                return str_replace('-', '', sanitize_title($code)) . ( strstr($code, '*') ? '*' : '' );
            }

            /*
             * validate_shipping_options_table_field function
             */
            function validate_shipping_options_table_field( $key ) {
                return false;
            }

            /*
             * generate_options_table_html function
             */
            function generate_shipping_options_table_html() {
                ob_start();
                ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc"><?php _e('Pickup Options', 'mx-wc-local-pickup-extended'); ?>:</th>
                        <td class="forminp" id="<?php echo $this->id; ?>_options">
                        <table class="shippingrows widefat" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="check-column"><input type="checkbox"></th>
                                    <th class="options-th"><?php _e('Option', 'mx-wc-local-pickup-extended'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                $i = -1;
                                if ($this->shipping_options) :
                                    foreach ($this->shipping_options as $option) :
                                        $i++;
                            ?>
                                        <tr class="option-tr">
                                            <th class="check-column"><input type="checkbox" name="select" /></th>
                                            <td><input style="width:60%;" type="text" name="<?php echo esc_attr($this->id . '_options[' . $i . ']') ?>" value="<?php echo $option; ?>"></td>
                                        </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4"><a href="#" class="add button"><?php _e('Add Option', 'mx-wc-local-pickup-extended'); ?></a> <a href="#" class="remove button"><?php _e('Delete selected options', 'mx-wc-local-pickup-extended'); ?></a></th>
                                </tr>
                            </tfoot>
                        </table>
                        <script type="text/javascript">
                            jQuery(function() {

                                jQuery('#<?php echo $this->id; ?>_options').on( 'click', 'a.add', function(){
                                    var size = jQuery('#<?php echo $this->id; ?>_options tbody .option-tr').size();
                                    jQuery('<tr class="option-tr"><th class="check-column"><input type="checkbox" name="select" /></th>' +
                                           '<td><input type="text" name="<?php echo esc_attr($this->id . '_options') ?>[' + size + ']" /></td></tr>')
                                        .appendTo('#<?php echo $this->id; ?>_options table tbody');
                                    return false;
                                });

                                // Remove row
                                jQuery('#<?php echo $this->id; ?>_options').on( 'click', 'a.remove', function(){
                                    var answer = confirm("<?php _e('Delete the selected options?', 'mx-wc-local-pickup-extended'); ?>");
                                    if (answer) {
                                        jQuery('#<?php echo $this->id; ?>_options table tbody tr th.check-column input:checked').each(function(i, el){
                                                jQuery(el).closest('tr').remove();
                                        });
                                    }
                                    return false;
                                });

                            });
                        </script>
                        </td>
                    </tr>
                <?php
                return ob_get_clean();
            }

            /*
             * process_shipping_options function.
             */
            function process_shipping_options() {

                $options = array();

                if (isset($_POST[$this->id . '_options']))
                    $options = array_map('wc_clean', $_POST[$this->id . '_options']);

                update_option($this->options_array_label, $options);

                $this->get_shipping_options();
            }

            /*
             * get_shipping_options function.
             */
           function get_shipping_options() {
                   $this->shipping_options = array_filter( (array) get_option( $this->options_array_label ) );
           }

           function mx_review_order_shipping_options() {
                global $woocommerce;
                $chosen_method = $woocommerce->session->get('chosen_shipping_methods');
                if (is_array($chosen_method) && in_array($this->id, $chosen_method)) {
                    echo '<tr class="shipping_option">';
                    echo '<th> '.__('Choose pickup location', 'mx-wc-local-pickup-extended').': </th>';
                    echo '<td><select style="max-width:150px;" name="shipping_option" class="input-select" id="shipping_option" required="required">';
                    echo '<option value=""> '.__('Select Location', 'mx-wc-local-pickup-extended').' </option>';
                    foreach ($this->shipping_options as $option) {
                        echo '<option value="' . esc_attr($option) . '" ' . selected( $woocommerce->session->_chosen_shipping_option, esc_attr($option) ) . '>' . $option . '</option>';
                    }
                    echo '</select></td></tr>';

                    ?>
                        <script>
                            var options = document.getElementsByName("shipping_option");
                            if (options.length >= 1) {
                                options[0].addEventListener("change", function() {
                                    var data = "action=mx_save_selected&shipping_option=" + this.value;
                                    var xmlhttp;
                                    if (window.XMLHttpRequest) {
                                        xmlhttp = new XMLHttpRequest();
                                    } else {
                                        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
                                    }
                                    xmlhttp.open('GET', '<?php echo admin_url( 'admin-ajax.php' ); ?>?' + data, true);
                                    xmlhttp.send();
                                });
                            }
                        </script>
                    <?php
                }
            }

            function mx_field_update_shipping_order_meta( $order_id, $posted ) {
                global $woocommerce;
                if (is_array($posted['shipping_method']) && in_array($this->id, $posted['shipping_method'])) {
                    if ( isset( $_POST['shipping_option'] ) && !empty( $_POST['shipping_option'] ) ) {
                        update_post_meta( $order_id, 'mx_shipping_option', sanitize_text_field( $_POST['shipping_option'] ) );
                        $woocommerce->session->_chosen_shipping_option = sanitize_text_field( $_POST['shipping_option'] );
                    }
                } else {
                    $chosen_method = $woocommerce->session->get('chosen_shipping_methods');
                    $chosen_option= $woocommerce->session->_chosen_shipping_option;
                    if (is_array($chosen_method) && in_array($this->id, $chosen_method) && $chosen_option) {
                        update_post_meta( $order_id, 'mx_shipping_option', $woocommerce->session->_chosen_shipping_option );
                    }
                }
            }

            function mx_display_shipping_admin_order_meta($order){
                $selected_option = get_post_meta( $order->id, 'mx_shipping_option', true );
                if ($selected_option) {
                    echo '<p><strong>' . $this->title . ':</strong> ' . get_post_meta( $order->id, 'mx_shipping_option', true ) . '</p>';
                }
            }

            function add_mx_shipping_methods( $methods ) {
                $methods[] = $this;
                return $methods;
            }

			function negative_pickup_label( $label, $method ){
                 if ( ! is_admin() && $method->id == 'mx_local_shipping' ) {
                 $label = $method->label . ' : ' . wc_price( $method->cost );
                }
              return $label;
            }


        }

        new MXWC_Shipping_Options();

    }
	
	       /*
            * display pickup location meta on the Order details page
            */
		   
	       add_action( 'woocommerce_order_details_after_order_table', 'mx_shipping_display_custom_order_meta', 10, 1 );

           function mx_shipping_display_custom_order_meta($order){
		      $selected_option = get_post_meta( $order->id, 'mx_shipping_option', true );
              if ($selected_option) {
                  echo '<p><strong>'.__('Pickup Location', 'mx-wc-local-pickup-extended').':</strong> ' . get_post_meta( $order->id, 'mx_shipping_option', true ). '</p>';
              }
           }
	      
		   /*
            * add pickup location meta to order emails
            */
		   
           add_filter('woocommerce_email_order_meta_keys', 'mx_shipping_option_meta_keys');

             function mx_shipping_option_meta_keys( $keys ) {
				if ( isset( $_POST['shipping_option'] ) && !empty( $_POST['shipping_option'] ) ) {
	            echo '<h2>'.__('Pickup Location', 'mx-wc-local-pickup-extended').':</h2>';

	            $keys[''] = 'mx_shipping_option';
                return $keys;
                }
            }

         add_action('woocommerce_shipping_init', 'mx_shipping_methods_init');
         add_action( 'wp_ajax_mx_save_selected', 'mx_save_selected' );
         add_action( 'wp_ajax_nopriv_mx_save_selected', 'mx_save_selected' );
           function mx_save_selected() {
                 if ( isset( $_GET['shipping_option'] ) && !empty( $_GET['shipping_option'] ) ) {
            global $woocommerce;
            $selected_option = $_GET['shipping_option'];
            $woocommerce->session->_chosen_shipping_option = sanitize_text_field( $selected_option );
        }
        die();
    }
}

?>
