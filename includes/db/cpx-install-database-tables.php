<?php

defined( 'ABSPATH' ) || exit;

/**
 * Crea las tablas necesarias para hacer los reportes.
 *
 * @version 7.1.16 [Funciona para la versión 7.1.16 del plugin QSM Analysis and Reports].
 *  
 * @param  [array] $quiz_result [Arreglo con la información del quiz]
 * @return [void]
 */
function cpx_qr_create_plugin_tables() {

    global $wpdb;
    $table_name = $wpdb->prefix . "cpx_quiz_results";
    $charset_collate = $wpdb->get_charset_collate();

    // Si la tabla no existe.
    if( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
    	// Crea la tabla.
  		$sql = "CREATE TABLE $table_name (
  			id mediumint(9) NOT NULL AUTO_INCREMENT,
  			user_id mediumint(9) NOT NULL,
  			quiz_id mediumint(9) NOT NULL,
            question_title varchar(255) NOT NULL,
  			question_id mediumint(9) NOT NULL,
  			answer TEXT NOT NULL,
            section_title varchar(255) NOT NULL,
  			
  			PRIMARY KEY  (id)
  		) $charset_collate;";

  		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  		dbDelta( $sql );
  	}

    $view_name = $wpdb->prefix . "cpx_answer_frequency";

    // Si la vista no existe.
    if( $wpdb->get_var( "SHOW TABLES LIKE '$view_name'" ) != $view_name ) {
    	// Crea la vista.
  		$sql = "CREATE VIEW $view_name AS
		    SELECT
			    cpx.answer,
          cpx.quiz_id,
			    cpx.section_title,
			    qsm.quiz_name,
			    cpx.question_id,
			    cpx.question_title,
			    COUNT(cpx.user_id) AS answer_frequency
			FROM
			    `$table_name` AS cpx
			LEFT JOIN " . $wpdb->prefix . "mlw_quizzes AS qsm ON cpx.quiz_id = qsm.quiz_id
			GROUP BY
			    cpx.question_id,
			    cpx.answer
  		;";

  		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

  		// Ejecuta el query.
  		$wpdb->get_results( $sql );
  	}

  	$view_name = $wpdb->prefix . "cpx_all_answers";

    // Si la vista no existe.
    if( $wpdb->get_var( "SHOW TABLES LIKE '$view_name'" ) != $view_name ) {
    	// Crea la vista.
  		$sql = "CREATE VIEW $view_name AS
		    SELECT
                cpx.quiz_id,
			    cpx.section_title,
			    qsm.quiz_name,
			    cpx.question_id,
			    cpx.question_title,
			    cpx.user_id,
			    users.user_email,
			    cpx.answer
			FROM
			    `" . $wpdb->prefix . "cpx_quiz_results` AS cpx
			LEFT JOIN " . $wpdb->prefix . "mlw_quizzes AS qsm ON cpx.quiz_id = qsm.quiz_id
			LEFT JOIN " . $wpdb->prefix . "users AS users ON cpx.user_id = users.ID
			GROUP BY
			    cpx.quiz_id,
			    cpx.question_id,
			    cpx.user_id
  		;";

  		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

  		// Ejecuta el query.
  		$wpdb->get_results( $sql );
  	}
}



?>