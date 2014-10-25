<?php
namespace Lex\GiddyORM;

function is_blank($str) {
	return strlen($str) == 0;
}

class Util {
	private static $__to_plural = array(
        '/(quiz)$/i' => "$1zes",
        '/^(ox)$/i' => "$1en",
        '/([m|l])ouse$/i' => "$1ice",
        '/(matr|vert|ind)ix|ex$/i' => "$1ices",
        '/(x|ch|ss|sh)$/i' => "$1es",
        '/([^aeiouy]|qu)y$/i' => "$1ies",
        '/(hive)$/i' => "$1s",
        '/(?:([^f])fe|([lr])f)$/i' => "$1$2ves",
        '/(shea|lea|loa|thie)f$/i' => "$1ves",
        '/sis$/i' => "ses",
        '/([ti])um$/i' => "$1a",
        '/(tomat|potat|ech|her|vet)o$/i'=> "$1oes",
        '/(bu)s$/i' => "$1ses",
        '/(alias)$/i' => "$1es",
        '/(octop)us$/i' => "$1i",
        '/(ax|test)is$/i' => "$1es",
        '/(us)$/i' => "$1es",
        '/s$/i' => "s",
        '/$/' => "s"
    );
    
	private static $__to_singular = array(
        '/(quiz)zes$/i' => "$1",
        '/(matr)ices$/i' => "$1ix",
        '/(vert|ind)ices$/i' => "$1ex",
        '/^(ox)en$/i' => "$1",
        '/(alias)es$/i' => "$1",
        '/(octop|vir)i$/i' => "$1us",
        '/(cris|ax|test)es$/i' => "$1is",
        '/(shoe)s$/i' => "$1",
        '/(o)es$/i' => "$1",
        '/(bus)es$/i' => "$1",
        '/([m|l])ice$/i' => "$1ouse",
        '/(x|ch|ss|sh)es$/i' => "$1",
        '/(m)ovies$/i' => "$1ovie",
        '/(s)eries$/i' => "$1eries",
        '/([^aeiouy]|qu)ies$/i' => "$1y",
        '/([lr])ves$/i' => "$1f",
        '/(tive)s$/i' => "$1",
        '/(hive)s$/i' => "$1",
        '/(li|wi|kni)ves$/i' => "$1fe",
        '/(shea|loa|lea|thie)ves$/i'=> "$1f",
        '/(^analy)ses$/i' => "$1sis",
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i'  => "$1$2sis",
        '/([ti])a$/i' => "$1um",
        '/(n)ews$/i' => "$1ews",
        '/(h|bl)ouses$/i' => "$1ouse",
        '/(corpse)s$/i' => "$1",
        '/(us)es$/i' => "$1",
        '/(us|ss)$/i' => "$1",
        '/s$/i' => ""
    );
    
    private static $__irregular = array(
        'move'   => 'moves',
        'foot'   => 'feet',
        'goose'  => 'geese',
        'sex'    => 'sexes',
        'child'  => 'children',
        'man'    => 'men',
        'tooth'  => 'teeth',
        'person' => 'people'
    );

    private static $__uncountable = array(
        'sheep',
        'fish',
        'deer',
        'series',
        'species',
        'money',
        'rice',
        'information',
        'equipment'
    );
	
	static function variabalize($str) {
		return str_replace(array(' ', '-', strtolower(trim($str))));
	}

	static function classify($str, $singularize = false) {
		if($singularize) {
			$str = self::singularize($str);
		}
		
		$str = self::camelize($str);
		
		return ucfirst($str);
	}
	
	
	static function keyfy($model_name) {
		return self::decamelize($model_name) . '_id';
	}
	
	static function camelize($string) {
		$string = preg_replace('/[_-]+/','_',trim($string));
		$string = str_replace(' ', '_', $string);
		
		for($i = 0; $i < strlen($string); $i++) {
			if($string[$i] == '_' && $i+1 < strlen($string)) {
				$string[$i+1] = strtoupper($string[$i+1]);
			}
		}
		
		$string = str_replace('_', '', $string);
		$string[0] = strtolower($string[0]);
		
		return $string;
	}
	
	static function decamelize($string) {
		$normalized = '';
		for($i = 0; $i < strlen($string); $i++) {
			if(ctype_alpha($string[$i]) &&
				strtolower($string[$i]) != $string[$i]) {
					
					$normalized .= "_" . strtolower($string[$i]);
			} else {
				$normalized .= $string[$i];
			}
		}
		
		return ltrim($normalized, '_');
	}

	static function pluralize($str) {
		if(in_array(strtolower($str), self::$__uncountable)) 
			return $str;
		
		foreach(self::$__irregular as $pattern => $replacement) {
			$pattern = '/' . $pattern . '$/i';
			if(preg_match($pattern, $str)) {
				return preg_replace($pattern, $replacement, $str);
			}
		}
		
		foreach(self::$__to_plural as $pattern => $replacement) {
			if(preg_match($pattern, $str)) {
				return preg_replace($pattern, $replacement, $str);
			}
		}
		
		return $str;
	}

	static function singularize($str) {
		if(in_array(strtolower($str), self::$__uncountable))
			return $str;
			
		foreach(self::$__irregular as $replacement => $pattern) {
			$pattern = '/' . $pattern . '$/i';
			if(preg_match($pattern, $str)) {
				return preg_replace($pattern, $replacement, $str);
			}
		}
		
		foreach(self::$__to_singular as $pattern => $replacement) {
			if(preg_match($pattern, $str)) {
				return preg_replace($pattern, $replacement, $str);
			}
		}
		
		return $str;
	}
}

?>
