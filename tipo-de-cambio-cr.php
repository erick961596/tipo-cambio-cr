<?php
/*
Plugin Name: Tipo de cambio dolar Costa Rica
Plugin URI: https://www.heyrocketcr.com
Description: Plugin para obtener el tipo de cambio de Costa Rica
Version:1.0
Author:Erick Pérez
Author URI:https://www.heyrocketcr.com
License:GPL2
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Exchange rate constants
define('WPTCR_COMPRA_CR', 317);
define('WPTCR_VENTA_CR', 318);

// WebService URL
define('WPTCR_IND_ECONOM_WS', "https://gee.bccr.fi.cr/Indicadores/Suscripciones/WS/wsindicadoreseconomicos.asmx");

// WebService method
define('WPTCR_IND_ECONOM_METH', "ObtenerIndicadoresEconomicosXML");

// Max number of hours to update the values
define('WPTCR_TIME_LIMIT', 4);

/**
 * Gets the current exchange rate
 *
 * @param string $tipo Exchange rate type (COMPRA/VENTA)
 * @param string $fecha Exchange rate date
 * @return float Exchange rate value
 */

function wptcrI_tipo_cambio($tipo = "", $fecha = ""){
    $correoElectronico ="correoElectronico@gmail.com";
    $tokenBCR="tokenBCR";
    $nombreRegistrado="nombreRegistrado";
    date_default_timezone_set('America/Costa_Rica');
    $fecha_tc = empty($fecha) ? date("d/m/Y") : $fecha;
    $fecha_actual = date('Y-m-d H:i:s');

    if(empty($tipo)){
        $tipo_tc = WPTCR_COMPRA_CR;
    } else if($tipo === "COMPRA"){
        $tipo_tc = WPTCR_COMPRA_CR;
    } else if($tipo === "VENTA"){
        $tipo_tc = WPTCR_VENTA_CR;
    } else {
        $tipo_tc = WPTCR_COMPRA_CR;
    }

    $tipo_cambio = 0;
    $url_ws = WPTCR_IND_ECONOM_WS . "/" . WPTCR_IND_ECONOM_METH . "?Indicador=" . $tipo_tc . "&FechaInicio=" . $fecha_tc . "&FechaFinal=" . $fecha_tc . "&Nombre=".$nombreRegistrado."&SubNiveles=N&CorreoElectronico=" . $correoElectronico ."&Token=" . $tokenBCR;

    if($tipo_tc == WPTCR_COMPRA_CR){
        $wptcr_compra_cr = get_option('WPTCR_COMPRA_CR');

        if(!$wptcr_compra_cr){
            $tipo_cambio = wptcrI_get_bccr_service_data($url_ws); 
            $op_compra = $tipo_cambio . '|' . $fecha_actual;
            add_option('WPTCR_COMPRA_CR', $op_compra);
        } else {
            $opts = explode('|', $wptcr_compra_cr);
            $diff = date_diff(date_create($opts[1]), date_create($fecha_actual));
            $tipo_cambio = $opts[0];

            if($diff->h >= WPTCR_TIME_LIMIT){
                $tipo_cambio = wptcrI_get_bccr_service_data($url_ws); 
                $op_compra = $tipo_cambio . '|' . $fecha_actual;
                update_option('WPTCR_COMPRA_CR', $op_compra);
            }
        }

    } else if($tipo_tc == WPTCR_VENTA_CR){
        $wptcr_venta_cr = get_option('WPTCR_VENTA_CR');

        if(!$wptcr_venta_cr){
            $tipo_cambio = wptcrI_get_bccr_service_data($url_ws); 
            $op_venta = $tipo_cambio . '|' . $fecha_actual;
            add_option('WPTCR_VENTA_CR', $op_venta);
        } else {
            $opts = explode('|', $wptcr_venta_cr);
            $diff = date_diff(date_create($opts[1]), date_create($fecha_actual));
            $tipo_cambio = $opts[0];

            if($diff->h >= WPTCR_TIME_LIMIT){
                $tipo_cambio = wptcrI_get_bccr_service_data($url_ws); 
                $op_venta = $tipo_cambio . '|' . $fecha_actual;
                update_option('WPTCR_VENTA_CR', $op_venta);
            }
        }
    }

    return (float)$tipo_cambio;
}

 /**
 * Converts an amount from colones to dollars
 *
 * @param $monto The amount to convert
 * @return float The converted amount
 */
function wptcrI_convertir_colones_dolares($monto) {
    $tc_venta = wptcrI_tipo_cambio(WPTCR_VENTA_CR);
    return ($tc_venta > 0) ? number_format(($monto / $tc_venta), 2, '.', '') : 0;
}

/**
 * Convert an amount from dollars to colones
 *
 * @param $monto The amount to convert
 * @return float The converted amount
 */
function wptcrI_convertir_dolares_colones($monto) {
    $tc_compra = wptcrI_tipo_cambio(WPTCR_COMPRA_CR);
    return ($tc_compra > 0) ? number_format(($monto * $tc_compra), 2, '.', '') : 0;
}

/**
 * Get data using CURL
 * @param  string $Url Webservice URL
 * @return $output Web service response
 */
function wptcrI_get_bccr_service_data($url) {
    if (!function_exists('curl_init')){ 
        die('CURL is not installed!');
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);

    $xml = simplexml_load_string($output);
    $tc = trim(strip_tags(substr($xml, strpos($xml, "<NUM_VALOR>"), strripos($xml, "</NUM_VALOR>"))));
    $tc_format = number_format($tc, 2);

    return $tc_format;
}

/**
 * SHORTCODES
 *
 */
function wptcrI_tipo_cambio_compra_shortcode() {
    ob_start();  
    echo wptcrI_tipo_cambio('COMPRA');
    return ob_get_clean();
}
add_shortcode('WPTCRI_TIPO_CAMBIO_COMPRA', 'wptcrI_tipo_cambio_compra_shortcode');

function wptcrI_tipo_cambio_venta_shortcode() {
    ob_start();  
    echo wptcrI_tipo_cambio('VENTA');
    return ob_get_clean();
}
add_shortcode('WPTCRI_TIPO_CAMBIO_VENTA', 'wptcrI_tipo_cambio_venta_shortcode');

function wptcrI_convertir_colones_dolares_shortcode($atts) {
    extract(shortcode_atts(array(
        'monto' => '100'
    ), $atts));
    ob_start();  
    echo wptcrI_convertir_colones_dolares($monto);
    return ob_get_clean();
}
add_shortcode('WPTCRI_CONVERTIR_COLONES_DOLARES', 'wptcrI_convertir_colones_dolares_shortcode');

function wptcrI_convertir_dolares_colones_shortcode($atts) {
    extract(shortcode_atts(array(
        'monto' => '100'
    ), $atts));
    ob_start();  
    echo wptcrI_convertir_dolares_colones($monto);
    return ob_get_clean();
}
add_shortcode('WPTCRI_CONVERTIR_DOLARES_COLONES', 'wptcrI_convertir_dolares_colones_shortcode');

?>
