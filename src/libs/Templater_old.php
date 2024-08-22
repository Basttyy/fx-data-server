<?php

namespace Basttyy\FxDataServer\libs;

use Exception;

class Templater_Old {

	static $blocks = array();
	static $cache_path;
	static $cache_enabled = FALSE;
    static $path;

	static function view($file, $path = "/", $data = array(), $get_output = false) {
        self::$path = __DIR__. $path;
		self::$cache_path = storage_path(). 'cache/';
		$cached_file = self::cache($file);
	    extract($data, EXTR_SKIP);
        if (!$get_output) {
	   	    require $cached_file;
            self::clearCache($cached_file);
        } else {
            ob_start();
            logger()->info(file_get_contents($cached_file));
            require $cached_file;
            $data = ob_get_clean();

            // logger()->info($data);
            self::clearCache($cached_file);
            return $data;
        }
	}

	static function cache($file) {
		if (!file_exists(self::$cache_path)) {
		  	mkdir(self::$cache_path, 0744);
		}
	    $cached_file = self::$cache_path . str_replace(array('/', '.html'), array('_', ''), $file . '.php');
	    if (!self::$cache_enabled || !file_exists($cached_file) || filemtime($cached_file) < filemtime($file)) {
			$code = self::includeFiles($file);
			$code = self::compileCode($code);
	        file_put_contents($cached_file, '<?php class_exists(\'' . __CLASS__ . '\') or exit; ?>' . PHP_EOL . $code);
	    }
		return $cached_file;
	}

	static function clearCache($file = null) {
        try {
            if (is_null($file)) {
                foreach(glob(self::$cache_path . '*') as $file) {
                    unlink($file);
                }
                return true;
            }
    
            return unlink($file);
        } catch (Exception $ex) {
            ///TODO: log to logger file
            return false;
        }
	}

	static function compileCode($code) {
		$code = self::compileBlock($code);
		$code = self::compileYield($code);
		$code = self::compileEscapedEchos($code);
		$code = self::compileEchos($code);
		$code = self::compilePHP($code);
		return $code;
	}

	static function includeFiles($file) {
		$code = file_get_contents(self::$path . $file);
		preg_match_all('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', $code, $matches, PREG_SET_ORDER);
		foreach ($matches as $value) {
			$code = str_replace($value[0], self::includeFiles($value[2]), $code);
		}
		$code = preg_replace('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', '', $code);
		return $code;
	}

	static function compilePHP($code) {
		return preg_replace('~\{%\s*(.+?)\s*\%}~is', '<?php $1 ?>', $code);
	}

	static function compileEchos($code) {
		return preg_replace_callback('~\{{\s*(.+?)\s*\}}~is', function ($matches) {
            return '<?php echo ' . str_replace(['.', '{{', '}}'], ['->', '', ''], $matches[0]) . ' ?>';
        }, $code);
	}

	static function compileEscapedEchos($code) {
		return preg_replace('~\{{{\s*(.+?)\s*\}}}~is', '<?php echo htmlentities($1, ENT_QUOTES, \'UTF-8\') ?>', $code);
	}

	static function compileBlock($code) {
		$pattern = '/{% ?block ?(.*?) ?%}(.*?){% ?endblock ?%}/is';
		preg_match_all($pattern, $code, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
		
		$stack = [];
		$blocks = [];
		foreach ($matches as $match) {
			$blockName = $match[1][0];
			$blockContent = $match[2][0];
			$blockStart = $match[0][1];
			$blockEnd = $blockStart + strlen($match[0][0]);
			
			while (!empty($stack) && $stack[count($stack) - 1]['end'] < $blockStart) {
				array_pop($stack);
			}
			
			if (empty($stack)) {
				$blocks[] = ['name' => $blockName, 'content' => $blockContent];
			} else {
				$stack[count($stack) - 1]['content'] = str_replace("{% yield $blockName %}", $blockContent, $stack[count($stack) - 1]['content']);
			}
			
			$stack[] = ['name' => $blockName, 'content' => $blockContent, 'end' => $blockEnd];
		}
		
		foreach ($blocks as $block) {
			if (!array_key_exists($block['name'], self::$blocks)) {
				self::$blocks[$block['name']] = '';
			}
			if (strpos($block['content'], '@parent') === false) {
				self::$blocks[$block['name']] = $block['content'];
			} else {
				self::$blocks[$block['name']] = str_replace('@parent', self::$blocks[$block['name']], $block['content']);
			}
			$code = str_replace("{% block {$block['name']} %}{$block['content']}{% endblock %}", '', $code);
		}
		
		return $code;
	}

	static function compileYield($code) {
		foreach(self::$blocks as $block => $value) {
			$code = preg_replace('/{% ?yield ?' . $block . ' ?%}/', $value, $code);
		}
		$code = preg_replace('/{% ?yield ?(.*?) ?%}/i', '', $code);
		return $code;
	}

}
