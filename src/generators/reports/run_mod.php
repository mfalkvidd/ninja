<?php

require_once( '../buildlib.php' );

require_once( 'op5/generators/orm/ORMBuilder.php' );

class orm_generator extends generator_module {
	protected function do_run() {
		require( 'structure.php' ); /* Sets $tables */

		$builder = new ORMBuilder();

		foreach( $tables as $name => $structure ) {
			$builder->generate_table($name, $tables);
		}

		$builder->generate_manifest( $tables );
	}
}

$generator = new orm_generator('reports');
$generator->run();
