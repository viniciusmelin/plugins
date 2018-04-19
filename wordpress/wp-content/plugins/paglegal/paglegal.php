<?php

/*
 * Plugin Name: PagLegal
 * Description: Este plugin adiciona forma de pagamento ao modulo woocommerce
 * Version: 1.0
 * Author: Vinicius Melin
 * License: GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function verificar_woocommerce_ativo() {
  
    if( !class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( __( 'É preciso instalar Woocommerce e ativar!', 'woocommerce-addon-slug' ), 'Woocommerce não encontrado!', array( 'back_link' => true ) );
    }
}

register_activation_hook(__FILE__, 'verificar_woocommerce_ativo');
add_action('plugins_loaded', 'init_paglegal_gateway_class');


function init_paglegal_gateway_class(){

    class WC_Gateway_Custom extends WC_Payment_Gateway {

        public $domain;

        public function __construct() {

            $this->domain = 'paglegal_payment';

            $this->id                 = 'paglegal';
            $this->icon               = apply_filters('woocommerce_paglegal_gateway_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'PagLegal', $this->domain );
            $this->method_description = __( 'Aceitar o pagamento via paglegal', $this->domain );
            $this->init_form_fields();
            $this->init_settings();
            $this->title        = $this->get_option( 'title' );
            $this->email        = $this->get_option( 'email' );
            $this->description  = $this->get_option( 'description' );
            $this->instructions = $this->get_option( 'instructions', $this->description );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_paglegal', array( $this, 'thankyou_page' ) );
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
        }

        public function checkValuesAdmin()
        {
            if(!isset($this->title) || empty($this->title))
            {
                wc_add_notice('payment', __( 'Cpf inválido!', $_POST['cpf']), 'error' );
            }
        }
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Ativar/Desativar', $this->domain ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Ativar PagLegal', $this->domain ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Nome', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'Nome da que será atribuido a forma de pagamento quando finalizar pedido.', $this->domain ),
                    'default'     => __( 'PagLegal', $this->domain ),
                    'desc_tip'    => true,
                ),
                'email' => array(
                    'title'       => __( 'Email', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'Email cadastrado no PagLegLegal', $this->domain ),
                    'default'     => __( 'exemplo@paglegal.com', $this->domain ),
                    'desc_tip'    => true,
                )
            );
        }

        public function thankyou_page() {
            if ( $this->instructions )
                echo wpautop( wptexturize( $this->instructions ) );
        }
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
            if ( $this->instructions && ! $sent_to_admin && 'paglegal' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }

        public function payment_fields(){
            ?>
            <div id="paglegal_input">
                <p class="form-row form-row-wide">
                    <label for="cpf" class=""><?php _e('CPF', $this->domain); ?></label>
                    <input type="text" class="" name="cpf" id="cpf" placeholder="Informe o CPF" value="" data-inputmask="'mask': '999.999.999-99'">
                </p>
            </div>
            <?php
        }
        public function process_payment( $order_id ) {

            $order = wc_get_order( $order_id );

            $status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;

            // Set order status
            $order->update_status( $status, __( 'Checkout com pagamento personalizado!. ', $this->domain ) );


            $order->reduce_order_stock();

            // Remove cart
            WC()->cart->empty_cart();

            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
        }
    }
}

add_filter( 'woocommerce_payment_gateways', 'add_paglegal_gateway_class' );
function add_paglegal_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_Custom'; 
    return $methods;
}

add_action('woocommerce_checkout_process', 'process_paglegal_payment');

function process_paglegal_payment(){

    if($_POST['payment_method'] != 'paglegal')
        return;

    if( !isset($_POST['cpf']) || empty($_POST['cpf']))
    {
        wc_add_notice( __( '<b>"CPF"</b> está vazio!'), 'error' );
    }

    if(!isset($_POST['cpf']) || !validar_cpf($_POST['cpf']))
    {
        wc_add_notice( __( '<b>"CPF"</b> está inválido!'), 'error' );
    }

}



add_action( 'woocommerce_checkout_update_order_meta', 'paglegal_payment_update_order_meta' );
function paglegal_payment_update_order_meta( $order_id ) {

    if($_POST['payment_method'] != 'paglegal')
        return;
    update_post_meta( $order_id, 'cpf', $_POST['cpf'] );
}

add_action( 'woocommerce_admin_order_data_after_billing_address', ' paglegal_checkout_field_display_admin_order_meta', 10, 1 );
function paglegal_checkout_field_display_admin_order_meta($order){
    $method = get_post_meta( $order->id, '_payment_method', true );
    if($method != 'paglegal')
        return;

    $cpf = get_post_meta( $order->id, 'cpf', true );

    
    echo '<p><strong>'.__( 'CPF' ).':</strong> ' . $cpf . '</p>';
}

function validar_cpf($cpf)
{
	$cpf = preg_replace('/[^0-9]/', '', (string) $cpf);
	if (strlen($cpf) != 11)
		return false;

	for ($i = 0, $j = 10, $soma = 0; $i < 9; $i++, $j--)
		$soma += $cpf{$i} * $j;
	$resto = $soma % 11;
	if ($cpf{9} != ($resto < 2 ? 0 : 11 - $resto))
		return false;

	for ($i = 0, $j = 11, $soma = 0; $i < 10; $i++, $j--)
		$soma += $cpf{$i} * $j;
	$resto = $soma % 11;
	return $cpf{10} == ($resto < 2 ? 0 : 11 - $resto);
}
