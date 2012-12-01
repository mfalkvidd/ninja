<?php

class LalrLexerJSGenerator extends js_class_generator {
	private $grammar;
	
	public function __construct( $parser_name, $grammar ) {
		$this->classname = $parser_name . "Lexer";
		$this->grammar = $grammar->get_tokens();
	}
	
	public function generate() {
		parent::generate();
		
		$this->init_class();
		$this->variable( 'buffer', '' );
		$this->variable( 'position', 0 );
		$this->generate_constructor();
		$this->generate_fetch_token();
		$this->finish_class();
	}
	
	private function generate_constructor() {
		$this->init_function( '__construct', array( 'buffer', 'visitor' ) );
		$this->write( 'this.buffer = buffer;' );
		$this->write( 'this.visitor = visitor;' );
		$this->finish_function();
	}
	
	private function generate_fetch_token() {
		$this->init_function( 'fetch_token', array() );
		
		$this->write('do {'); /* Until token is found */
		
		$this->write('length = -1;');
		$this->write('token = 0;');
		$this->write('value = 0;');
		$this->write('token_pos = this.position;');

		$this->write();
		$this->comment( "Match end token" );
		$this->write('if( this.buffer.length == 0 ) {');
		$this->write('length = 0;');
		$this->write('token = "end";');
		$this->write('}');
		
		foreach( $this->grammar as $name => $match ) {
			$this->write();
			$this->comment( "Match token: $name" );
			$this->write( 'var matches = this.buffer.match('.$match.');');
			$this->write( 'if( length == -1 ) {' );
			//$this-&& this.buffer.'.$match.', $this->buffer, $matches ) ) {' );
			$this->write(     '$length = strlen( $matches[1] );' );
			if( substr($name,0,1) != '_' ) {
				$this->write(     '$token = '.var_export( $name, true ).';' );
				$this->write(     '$value = $this->visitor->preprocess_'.$name.'($matches[1]);' );
			}
			$this->write( '}' );
		}
		
		$this->write();
		$this->comment( 'Exit if no match' );
		$this->write( 'if( $length === false ) return false;' );
		$this->write();
		$this->comment( 'Remove token from buffer, and move length forward' );
		$this->write( '$this->buffer = substr( $this->buffer, $length );');
		$this->write( '$this->position += $length; ');
		$this->write( '} while( $token === false );');
		$this->write( 'return array( $token, $value, $token_pos, $length );');
		
		$this->finish_function();
	}
}