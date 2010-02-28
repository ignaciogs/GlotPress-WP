<?php

class GP_Format_RRC {
	
	var $extension = 'rrc';
	
	function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$rrc = array();
		foreach( $entries as $entry ) {
			if ( !preg_match( '/^([A-Z0-9_]+)(?:\[|(\d+)\])$/', $entry->singular, $matches ) ) {
				error_log( 'RRC Export: Bad Entry: '.$entry->singular );
				continue;
			}
			if ( gp_array_get( $matches, 2 ) ) {
				$key = $matches[1];
				$index = $matches[2];
				$rrc[$key][$index] = $entry->translations[0];
			} elseif ( preg_match( )) {
				$rrc[$entry->singular] = $entry->translations[0];
			}
		}
		$result = '';
		foreach( $rrc as $key => $translation ) {
			if ( !is_array( $translation ) ) {
				$result .= "$key#0=" . $this->quote( $translation ) . ";\n";
			} else {
				$result .= "$key#0={\n";
				foreach( $translation as $single_translation ) {
					$result .= "\t" . $this->quote( $single_translation ) . ",\n";
				}
				$result .= "};\n";
			}
		}
		return $result;
	}
	
	function read_translations_from_file( $file_name ) {
		$entries = new Translations;
		$f = fopen( $file_name, 'r' );
		if ( !$f ) return false;
		$context = $index = $base_singular = $entry = null;
		while ( false !== ( $line = fgets( $f ) ) ) {
			$line = trim( $line );
			if ( is_null( $context) ) {
				// single line entry
				if ( preg_match( '/^([A-Z0-9_]+)\#0\s*=\s*"(.+)";$/', $line, $matches ) ) {
					$entry = new Translation_Entry();
					$entry->singular = $matches[1];
					$translation = $this->unescape( $matches[2] );
					// only one of the 2 fields is used for import
					// instead of choosing which field to populate, make our
					// lives easier and fill them both/
					$entry->extracted_comments = 'Original: ' . $translation;
					$entry->translations = array( $translation );
					$entries->add_entry( $entry );
				} elseif ( preg_match( '/^([A-Z0-9_]+)\#0\s*=\s*{$/', $line, $matches ) ) {
					$base_singular = $matches[1];
					$context = 'inside-multiple';
					$index = 0;
				} else {
					error_log("Bad line: $line");
					return false;
				}
			} elseif ( 'inside-multiple' == $context ) {
				if ( '};' == $line ) {
					$context = null;
				} elseif ( preg_match( '/^"(.*)",$/', $line, $matches ) ) {
					$entry = new Translation_Entry;
					$translation = $this->unescape( $matches[1] );
					$entry->singular = $base_singular . '[' . $index++ .']';					
					$entry->extracted_comments = 'Original: ' . $translation;
					$entry->translations = array( $translation );
					$entries->add_entry( $entry );					
				} else {
					error_log("Bad multiple line: $line");
					return false;
				}
			}
		}
		return $entries;
	}
	
	function escape( $string ) {
		$string = addcslashes( $string, "\"\n\t\r" );
		return $string;
	}
	
	function quote( $string ) {
		return '"' . $this->escape( $string ) . '"';
	}
	
	function unquote( $string ) {
		$string = trim( $string );
		return substr( $string, 1, strlen( $string ) - 2 );
	}
	
	function unescape( $string ) {
		return stripcslashes( $string );
	}
}

GP::$formats['rrc'] = new GP_Format_RRC;