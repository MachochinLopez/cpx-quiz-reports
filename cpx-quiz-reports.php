<?php 
/**
 * Plugin Name: CPX Quiz Reports Extension for QSM Quizzes
 * Description: Plugin para generar reportes de resultados en forma de tablas para los Quizzes del Plugin QSM.
 * Version: 1.0
 * Author: Copixil
 * Author URI: https://www.copixil.com
 */

defined( 'ABSPATH' ) || exit;
define( 'CPX_QUIZ_REPORTS_PATH', dirname( __FILE__ ) );


/**
 * Ejecuta todo lo necesario al activar el plugin.
 * 
 * @return void.
 */
function cpx_qr_activate_plugin() {
	// Si el plugin de QSM no está activado...
    if ( ! is_plugin_active( 'quiz-master-next/mlw_quizmaster2.php' ) ) {
		// Marca el error.
	    add_action('admin_notices', 'cpx_qr_failed_activation_notice');
	    deactivate_plugins( plugin_basename( __FILE__ ) );
	} else {

		// Requiere la clase que genera las tablas que requiere el plugin.
		require_once 'includes/db/cpx-install-database-tables.php';

		// Crea las tablas necesarias en la DB.
		cpx_qr_create_plugin_tables();
	}
}

// Al activar el plugin instala las tablas necesarias para la base de datos.
register_activation_hook( __FILE__, 'cpx_qr_activate_plugin' );


/**
 * Si los requerimientos se cumplen carga el plugin. Si no, devuelve un error.
 * 
 * @return bool
 */
function cpx_qr_load_plugin() {
	// Si el plugin de QSM no está activado...
    if ( ! is_plugin_active( 'quiz-master-next/mlw_quizmaster2.php' ) ) {
    	// Marca el error.
	    add_action('admin_notices', 'cpx_qr_failed_activation_notice');
	    deactivate_plugins( plugin_basename( __FILE__ ) );
    } else {
	    // Requiere la clase main del plugin.
		require_once 'includes/class-cpx-quiz-reports.php';

		// Instancia la clase prinicpal.
		$cpx_reports = new CPX_Quiz_Reports();
    }
}

// Inicia el plugin una vez que se haya cargado todo.
add_action('plugins_loaded', 'cpx_qr_load_plugin');


/**
 * Despliega un notice que informa que el Plugin de Quiz And Survey Master 
 * no está activado y es requisito para que este funcione.
 *
 * @return  void
 */
function cpx_qr_failed_activation_notice() {
    echo '<div class="error"><p>El plugin CPX Quiz Reports Extension for QSM Quizzes requiere que el plugin Quiz And Survey Master esté activado. Instálelo y actívelo antes de activar este plugin.</p></div>';
}

?>