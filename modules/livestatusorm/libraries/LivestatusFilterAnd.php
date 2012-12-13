<?php

class LivestatusFilterAnd extends LivestatusFilterBase {
	private $sub_filters = array();
	
	function get_sub_filters() {
		return $this->sub_filters;
	}
	
	function __clone() {
		$this->sub_filters = array_map(
				function($filt) {
					return clone $filt;
				},
				$this->sub_filters );
	}
	
	function prefix( $prefix ) {
		$res = new LivestatusFilterAnd();
		foreach( $this->sub_filters as $subf ) {
			$res->add( $subf->prefix( $prefix ) );
		}
		return $res;
	}
	
	function add( $filter ) {
		if( $filter instanceof self ) {
			foreach( $filter->sub_filters as $subf ) {
				$this->sub_filters[] = $subf;
			}
		} else {
			$this->sub_filters[] = $filter;
		}
	}

	function visit( LivestatusFilterVisitor $visitor, $data ) {
		return $visitor->visit_and($this, $data);
	}
}
