<?php

require CPX_QUIZ_REPORTS_PATH . '\includes\admin\class-answer-frequency-table.php';
require CPX_QUIZ_REPORTS_PATH . '\includes\admin\class-all-answers-table.php';

/**
 * Clase principal del plugin.
 */
class CPX_Quiz_Reports {

	/**
     * Constructor 
     *
     * @return void
     */
    public function __construct() {
        $this->cpx_qr_activate_plugin();
    }

	/**
	 * Define el comportamiento que ocurrirá apenas se active el plugin.
	 * 
	 * @return void
	 */
	public function cpx_qr_activate_plugin() {
		// Agrega el comportamiento después de que se dé submit a un quiz de QSM.
		add_action( 'qsm_quiz_submitted', array ($this, 'cpx_qr_log_submission_behaviour' ), 10, 4 );
		// Crea las tabs para ver los resultados en el Admin de Wordpress.
		add_action( 'admin_init', array( $this, 'cpx_qr_reports_register_tabs' ));
	}

	/**
	 * Registra las nuevas tabs para ver los resultados.
	 *
	 * @return void
	 */
	public function cpx_qr_reports_register_tabs() {
		// Llamamos al helper global del Plugin QSM.
		global $mlwQuizMasterNext;

		// Añade la pestaña.
	    $mlwQuizMasterNext->pluginHelper->register_admin_results_tab( "Porcentaje de respuestas", array($this, 'cpx_reports_answer_frequency_tab_content') );
	    // Añade la pestaña.
	    $mlwQuizMasterNext->pluginHelper->register_admin_results_tab( "Todas las respuestas", array($this, 'cpx_reports_all_answers_tab_content') );
	}

	/**
	 * Define el contenido de la vista de resultados de frecuencia de respuestas.
	 * 
	 * @return void
	 */
	public function cpx_reports_answer_frequency_tab_content() {
		// Carga la tabla.
		$table = new CPX_Answer_Frequency_Table();
		// Genera el contenido de la vista.
		$this->generate_tab_common_content($table);
	}

	/**
	 * Define el contenido de la vista de resultados.
	 * 
	 * @return void
	 */
	public function cpx_reports_all_answers_tab_content() {
		// Carga la tabla.
		$table = new CPX_All_Answers_Table();
		// Genera el contenido de la vista.
		$this->generate_tab_common_content($table);
	}

	/**
	 * Genera el contenido que comparten las vista de resultados.
	 * 
	 * @param  object $table Instancia de la tabla que se muestra en la vista.
	 * @return void
	 */
	private function generate_tab_common_content($table) {
		// Carga los estilos.
		wp_enqueue_style( 'cpx-reports-table-styles', plugin_dir_url( __DIR__ ) . '/css/cpx-reports-table-styles.css' );
		// Encola los estilos de font awesome del tema de ThimPress.
		wp_enqueue_style( 'font-awesome', THIM_URI . 'assets/css/all.min.css', array(), THIM_THEME_VERSION );

		// Prepara el comportamiento del botón para exportar a Excel.
		$table->export_to_csv();
		// Prepara el contenido de la tabla.
		$table->prepare_items();
		// Muestra la tabla.
		$table->display();
	}

	/**
	 * Define las acciones que ocurrirán después de que el se haga submit a un 
	 * quiz de QSM.
	 * 
	 * @param  array   $results_array             Arreglo con información del resultado.
	 * @param  integer $results_id                Id del resultado.
	 * @param  class   $qmn_quiz_options          Opciones del quiz.
	 * @param  array   $qmn_array_for_variables   Arreglo con toda la información que necesitamos.
	 *
	 * @return array Devolvemos los resultados que requiere el hook.
	 */
	public function cpx_qr_log_submission_behaviour(
		$results_array,
		$results_id,
		$qmn_quiz_options,
		$qmn_array_for_variables
	) {
		
		// Inserta el resultado a la tabla
		$this->cpx_qr_insert_answers($qmn_array_for_variables);
		
		return $qmn_array_for_variables;
	}

	/**
	 * Inserta las respuestas a la tabla cpx_qr_quiz_results.
	 *
	 * @version 7.1.16 Probado con la versión 7.1.16 del plugin QSM Analysis and Reports.
	 *  
	 * @param  array $quiz_result Arreglo con la información del quiz
	 * @return void
	 */
	public function cpx_qr_insert_answers( $quiz_result ) {
		// Inserta las respuestas en la db.
	    global $wpdb;
	    $table_name = $wpdb->prefix . "cpx_quiz_results";

	    // Bora todas las respuestas de este usuario en este cuestionario.
	    $wpdb->delete($table_name, array(
	    	'user_id' => $quiz_result['user_id'],
	    	'quiz_id' => $quiz_result['quiz_id']
	    ), array(
			'%d',
			'%d'
		));

	    // Por cada pregunta del quiz.
	    foreach ( $quiz_result['question_answers_array'] as $answer ) {

	    	// Si es una pregunta de opción múltiple (índice 0 de QSM Quiz Types).
	    	if ( $answer[ 'question_type'] == 0 ) {
		    	// Inserta la respuesta.
				$wpdb->insert( $table_name, array(
						'user_id' => $quiz_result['user_id'],
						'quiz_id' => $quiz_result['quiz_id'],
						'question_id' => $answer['id'],
						'section_title' => $answer['category'],
						'question_title' => $answer['question_title'],
						'answer' => $answer[1],
					), array(
						'%d',
						'%d',
						'%d',
						'%s',
						'%s',
						'%s',
					)
				);
	    	}
	    }
	}
}



?>