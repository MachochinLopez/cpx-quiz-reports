<?php

/**
 * Tabla de frecuencias.
 */
class CPX_Answer_Frequency_Table extends WP_List_Table {
	/**
    * Constructor, we override the parent to pass our own arguments
    * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
    */
    public function __construct() {
       	parent::__construct( array(
	      	'singular'=> 'Frecuencia', //Singular label
	      	'plural' => 'Frecuencias', //plural label, also this well be one of the table css class
	      	'ajax'   => false //We won't support Ajax for this table
  		) );
    }

   	/**
   	 * Define las columnas de la tabla.
   	 * 
   	 * @return array
   	 */
	public function get_columns() {
	    $columns = array(
			'section_title' => 'Taller',
			'quiz_name' => 'Cuestionario',
			'question_title' => 'Pregunta',
			'answer' => 'Respuesta',
			'answer_frequency' => 'Porcentaje',
		);

	    return $columns;
	}

	/**
	* Query
	*
	* @param integer $per_page
	* @param integer $page_number
	*
	* @return mixed
	*/
	public function get_records( $per_page = 10000000, $page_number = 1 ) {

		global $wpdb;
		// Forma el Query.
		$sql = "SELECT * FROM {$wpdb->prefix}cpx_answer_frequency";
		// Revisa los filtros de búsqueda.
		$sql = $this->filter_records($sql);

		// Define el ordenamiento.
		if ( ! empty( $_REQUEST[ 'orderby' ] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST[ 'orderby' ] );
			$sql .= ! empty( $_REQUEST[ 'order' ] ) ? ' ' . esc_sql( $_REQUEST[ 'order' ] ) : ' ASC';
		} else {
			$sql .= ' ORDER BY quiz_id ASC ';
		}

		// Limita los resultados por página.
		$sql .= " LIMIT $per_page";
		// Pasa la página.
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

		// Prepara el query.
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		// Formatea los records obtenidos.
		$result = $this->format_records($result);

		return $result;
	}

	/**
	 * Le da el formato a cada una de las respuestas para no repetir
	 * el nombre del cuestionario ni el título de la pregunta.
	 * 
	 * @param  array $records_array [Resultados de la vista]
	 * @return array
	 */
	private function format_records( $records_array ) {
		$result = array();

		// Por cada row...
		foreach ( $records_array as $row ) {
			
			// Si no se ha inicializado el índice de la pregunta actual...
			if ( ! isset( $result[ $row[ 'question_id' ] ] ) ) {

				// Lo inicializa.
				$result[ $row[ 'question_id' ] ] = array(
					'section_title' => $row[ 'section_title' ],
					'quiz_name' => $row[ 'quiz_name' ],
					'question_id' => $row[ 'question_id' ],
					'question_title' => $row[ 'question_title' ],
					'answer' => "<p>{$row[ 'answer' ]}</p>",
					// Este lo declara como un arreglo vacío.
					'answer_frequency' => array (),
				);

				// Y luego le agrega el primer valor.
				array_push( $result[ $row[ 'question_id' ] ][ 'answer_frequency' ], $row[ 'answer_frequency' ] );

			} else {
				// Pasa un enter y luego escribe la respuesta y su porcentaje.
				$result[ $row[ 'question_id' ] ][ 'answer' ] .= "<p>{$row[ 'answer' ]}</p>";
				// Agrega la respuesta al arreglo de frecuencias.
				array_push( $result[ $row[ 'question_id' ] ][ 'answer_frequency' ], $row[ 'answer_frequency' ] );
			}
		}

		// Por cada row formateada...
		foreach ( $result as $row ) {

			// Cuenta cuántas respuestas había.
			$answer_amount = array_reduce( $row[ 'answer_frequency' ], function( $carry, $item ) {
				$carry += $item;
				return $carry;
			} );

			$max_value = max( $row[ 'answer_frequency' ] );
			$min_value = min( $row[ 'answer_frequency' ] );

			// Guarda el valor de la row en una variable auxiliar.
			$aux_row = $row;

			// Sobreescribe la variable original, quitando el arreglo y sustituyéndolo por
			// un string.
			$result[ $row[ 'question_id' ] ][ 'answer_frequency' ] = '';

			// Por cada valor de frecuencia...
			foreach ( $aux_row[ 'answer_frequency' ] as $frequency ) {
				$is_max_value = ($frequency === $max_value) ? 'max-percentage' : '' ;
				$is_min_value = ($frequency === $min_value) ? 'min-percentage' : '' ;

				// Calcula el porcentaje.
				$percentage = round( ( $frequency * 100 / $answer_amount ), 1);
				// Lo concatena al string pasando un enter.
				$result[ $row[ 'question_id' ] ][ 'answer_frequency' ] .= "<p class='{$is_max_value} {$is_min_value}'>{$percentage}%</p>";
			}
		}

		return $result;
	}

	/**
	 * Devuelve el query con la WHERE clause para la búsqueda.
	 * 
	 * @param  string $sql [query string]
	 * @return string
	 */
	private function filter_records ($sql) {
		$result = $sql;

		// Si se hizo una búsqueda...
		if ( isset( $_GET[ 'search_phrase' ] ) && $_GET[ 'search_phrase' ] != '' ) {
			$keyword = '%'.sanitize_text_field($_GET[ 'search_phrase' ]).'%';
			$result .= " WHERE section_title LIKE '{$keyword}' OR quiz_name LIKE '{$keyword}' OR question_title LIKE '{$keyword}'";
		}

		return $result;
	}

	/**
	 * Prepara el contenido de la tabla.
	 * 
	 * @return void
	 */
	public function prepare_items() {
		$columns = $this->get_columns(); 
		$hidden = array(); 
		$sortable = array(); 
		$this->_column_headers = array( $columns, $hidden, $sortable ); 
		$this->items = $this->get_records();
	}

	/**
	 * Define qué va a regresar cada columna.
	 * 
	 * @param  array  $item        [row]
	 * @param  string $column_name [nombre de la columna]
	 * 
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch( $column_name ) { 
			case 'section_title':
			case 'quiz_name':
			case 'question_title':
			case 'answer':
			case 'answer_frequency':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; // Mostramos todo el arreglo para resolver problemas
		}
	}


	/**
	 * Agrega el filtro de búsqueda. 
	 * 
	 * @param  string $which [Indica si es el tablenav de arriba o abajo de la tabla
	 *                  (no es relevante para esta función.)]
	 * @return void
	 */
	public function display_tablenav($which) {
	    ?>
			<div class="tablenav <?php echo esc_attr( $which ); ?>">
			 
			    <form action="" method="GET">
		            <input type="hidden" name="page" value="mlw_quiz_results">
		            <input type="hidden" name="tab" value="porcentaje-de-respuestas">
		            <p class="search-box">
		                <label for="search_phrase">Buscar</label>
		                <input type="search" id="search_phrase" name="search_phrase" value="">
		                <button class="button">Buscar</button>
		            </p>
		        </form>

		        <form action="" method="POST">
		        	<input type="hidden" name="download_csv" value="true">
		            <button class="button"> <i class="fas fa-file-excel"></i> Exportar</button>
		        </form>
			 
			    <br class="clear" />
			</div>
	    <?php
	}

	/**
	 * Exporta a excel los datos.
	 * 
	 * @return void
	 */
	public function export_to_csv() {

		if ( isset( $_POST[ 'download_csv' ] ) ) {
			$records = $this->get_records();
			// Si hay contenido.
			if ($records) {
	  			
	  			// Define los headers.
	            ob_clean();
		        header( 'Pragma: public' );
		        header( 'Expires: 0' );
		        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		        header( 'Cache-Control: private', false );
		        header( 'Content-Type: text/csv' );
		        header( 'Content-Disposition: attachment;filename=Reporte.csv' );
	  
	            $file = fopen('php://output', 'w');
	  
	  			// Agrega la row con los headers.
	            fputcsv( $file, array(
		            	'Taller',
		            	'Cuestionario',
		            	'Pregunta',
		            	'Respuesta',
		            	'Porcentaje'
	            	)
	        	);
	  
	            // Por cada fila...
	            foreach ( $records as $row ) {
	            	// Separa las respuestas y las frecuencias.
	            	$answers = preg_split( "/<\/p>/", $row[ 'answer' ], NULL, PREG_SPLIT_NO_EMPTY );
	            	$frequencies = preg_split( "/<\/p>/", $row[ 'answer_frequency' ], NULL, PREG_SPLIT_NO_EMPTY );
					
					// Por cada respuesta.
					foreach ( $answers as $key => $answer ) {
						$row_array = array();

						// Si es la primera...
						if ($key === 0) {
							$row_array = array(	
								utf8_decode($row[ 'section_title' ]),
			                	utf8_decode($row[ 'quiz_name' ]),
			                	utf8_decode($row[ 'question_title' ]),
			                	utf8_decode(strip_tags(html_entity_decode( $answers[ $key ] ))),
			                	utf8_decode(strip_tags(html_entity_decode( $frequencies[ $key ] ))),
							);
						} else {
							// Deja los espacios vacíos y luego escribe sólamente las respuestas
							// y frecuencias.
							$row_array = array(	
								'',
			                	'',
			                	'',
			                	utf8_decode(strip_tags(html_entity_decode( $answers[ $key ] ))),
			                	utf8_decode(strip_tags(html_entity_decode( $frequencies[ $key ] ))),
							);
						}

						// Imprime la row.
						fputcsv($file, $row_array);
					}
	            }

	            // Cierra el archivo.
	  			fclose( $file );
		        ob_flush();

	            exit();
	        }
	    }
	}
}



?>