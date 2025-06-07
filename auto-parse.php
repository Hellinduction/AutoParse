<?php
    /**
     * This file provides an extremely easy way to parse custom tags into the values of PHP variables.
     * It works by listening to the ob_start callback, which effectively waits until the PHP script has finished executing,
     * and then processes the output buffer to replace any custom tags with the values of the variables they reference.
     * 
     * In order to use this safetly, it is importatnt to sanitize any user input being displayed on the page (using htmlspecialchars),
     * as to ensure a global/session variable is not echoed back to the client unintentionally.
     * 
     * To start using this, all you have to do is include this file at the top of your PHP script (or after you have called session_start())
     * This can be done via the following line: include_one "includes/auto-parse.php";
     * Global variables are already accessible, however in order to register a local variable for later reference, you must do register_local_variable($var, $ref)
     * It is important to use "global $var;" when redeclaring a global variable in order for this file to get the latest value for that variable.
     * 
     * In order to actually access PHP variables in HTML, you can do the following examples:
     * - <session:userid/> - A simple example that would replace this element with whatever the value of $_SESSION['userid'] is (assuming it exists).
     * - <user/> - Say if you had a global variable called $user, this would simply display the contents of that variable.
     * - <user:username/> - Assuming our user variable is an object/array and has a key called 'username', this would be replaced with the the that value.
     * 
     * It is possible to chain variables together when accessing sub-variables by their key, like when they are in an array or object.
     * It is also possible to call functions on variables by apppending `()` to the end of the variable name.
     * If you append "::json" to the end of a tag, it will convert whatever the primitive/object/array is to JSON
     * The actual value of the variable is sanitized by default, but you can disable this by appending a `~` to the end of the tag before the slash.
     */

    $LOCAL_VARIABLE_REGISTRY = [];

    /**
     * Registers a local variable and returns a unique key for it.
     * This unique key can be used to to reference the variable in any custom tag returned to the client prefixed with `registry:`.
     */
    function register_local_variable($variable, ?string &$key = null): string {
        global $LOCAL_VARIABLE_REGISTRY;

        $bytes = random_bytes(ceil(64 / 2));
        $key = bin2hex($bytes);
        $LOCAL_VARIABLE_REGISTRY[$key] = $variable;

        return $key;
    }

    function splitOutsideParentheses(string $input, string $delimiter = ':'): array {
        $result = [];
        $buffer = '';
        $depth = 0;

        $length = strlen($input);
        for ($i = 0; $i < $length; $i++) {
            $char = $input[$i];

            if ($char === '(') {
                $depth++;
                $buffer .= $char;
            } elseif ($char === ')') {
                if ($depth > 0) $depth--;
                $buffer .= $char;
            } elseif ($char === $delimiter && $depth === 0) {
                $result[] = $buffer;
                $buffer = '';
            } else {
                $buffer .= $char;
            }
        }

        if ($buffer !== '') {
            $result[] = $buffer;
        }

        return $result;
    }

    function post_process($post_processor, $value, $source = null, $parts): string {
            switch ($post_processor) {
                case 'json':
                case 'pjson':
                case 'jsonp':
                case 'prettyjson':
                case 'json-p':
                case 'pretty-json':
                    $pretty = $post_processor !== 'json';
                    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
                    if ($pretty) $flags |= JSON_PRETTY_PRINT;
                    $value = json_encode($value, $flags);
                    break;
                case 'length':
                    $value = is_string($value) ? strlen($value) : '';
                    break;
                case 'count':
                    $value = (is_array($value) || $value instanceof Countable) ? count($value) : '';
                    break;
                case 'upper':
                    $value = is_string($value) ? strtoupper($value) : '';
                    break;
                case 'lower':
                    $value = is_string($value) ? strtolower($value) : '';
                    break;
                case 'unset':
                    if ($source === 'session' && count($parts) === 1) {
                        unset($_SESSION[$parts[0]]);
                    }
                    $value = '';
                    break;
                case null:
                    break;
                default:
                    return '';   
            }

            return $value;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    ob_start(function ($buffer) {
        if (str_contains(__DIR__, "includes")) {
            chdir(__DIR__ . "/..");
        }

        return preg_replace_callback(
            '/<([a-zA-Z0-9_\-:()\'",\s]+?)(?:::([a-zA-Z\-]+))?(~?)\/>/',
            function ($matches) {
                global $_SESSION, $_POST, $_GET, $_COOKIE, $_SERVER, $LOCAL_VARIABLE_REGISTRY;

                $tag = $matches[1];
                $post_processor = $matches[2] ?? null;
                $sanitize = $matches[3] !== '~';

                $parts = splitOutsideParentheses($tag);
                $source = array_shift($parts);

                $value = match ($source) {
                    'session' => $_SESSION,
                    'post'    => $_POST,
                    'get'     => $_GET,
                    'cookie'  => $_COOKIE,
                    'server'  => $_SERVER,
                    'registry'=> $LOCAL_VARIABLE_REGISTRY,
                    default   => $GLOBALS[$source] ?? null,
                };

                foreach ($parts as $segment) {
                    if (preg_match('/^([a-zA-Z0-9_]+)\((.*)\)$/', $segment, $call_match)) {
                        $method = $call_match[1];
                        $raw_args = trim($call_match[2]);

                        $args = [];

                        if ($raw_args !== '') {
                            $args_raw = preg_split('/,(?=(?:[^\'"]*["\'][^\'"]*["\'])*[^\'"]*$)/', $raw_args);
                            foreach ($args_raw as $arg) {
                                $arg = trim($arg);

                                if ((str_starts_with($arg, "'") && str_ends_with($arg, "'")) ||
                                    (str_starts_with($arg, '"') && str_ends_with($arg, '"'))) {
                                    $args[] = stripslashes(substr($arg, 1, -1));
                                } elseif (preg_match('/^([a-z]+):([a-zA-Z0-9:_-]+)$/', $arg, $var_match)) {
                                    $ref_source = $var_match[1];
                                    $ref_path = explode(':', $var_match[2]);

                                    $ref = match ($ref_source) {
                                        'session'  => $_SESSION,
                                        'post'     => $_POST,
                                        'get'      => $_GET,
                                        'cookie'   => $_COOKIE,
                                        'server'   => $_SERVER,
                                        'registry' => $LOCAL_VARIABLE_REGISTRY,
                                        default    => $GLOBALS[$ref_source] ?? null,
                                    };

                                    foreach ($ref_path as $ref_segment) {
                                        if (is_array($ref) && isset($ref[$ref_segment])) {
                                            $ref = $ref[$ref_segment];
                                        } elseif (is_object($ref) && (isset($ref->$ref_segment) || method_exists($ref, '__get'))) {
                                            $ref = $ref->$ref_segment;
                                        } else {
                                            $ref = null;
                                            break;
                                        }
                                    }

                                    $args[] = $ref;
                                } elseif (is_numeric($arg)) {
                                    $args[] = $arg + 0;
                                } elseif (strtolower($arg) === 'true') {
                                    $args[] = true;
                                } elseif (strtolower($arg) === 'false') {
                                    $args[] = false;
                                } elseif (strtolower($arg) === 'null') {
                                    $args[] = null;
                                } else {
                                    $args[] = null;
                                }
                            }
                        }

                        if (is_object($value) && method_exists($value, $method)) {
                            $value = call_user_func_array([$value, $method], $args);
                        } elseif (function_exists($method) && $value === null) {
                            $value = call_user_func_array($method, $args);
                        } else {
                            return '';
                        }
                    } else {
                        if (is_array($value) && array_key_exists($segment, $value)) {
                            $value = $value[$segment];
                        } elseif (is_object($value) && (isset($value->$segment) || method_exists($value, '__get'))) {
                            $value = $value->$segment;
                        } else {
                            return '';
                        }
                    }
                }

                $value = post_process($post_processor, $value, $source, $parts);

                return is_array($value) || is_object($value)
                    ? ($sanitize ? htmlspecialchars(print_r($value, true)) : print_r($value, true))
                    : ($sanitize ? htmlspecialchars((string)$value) : (string)$value);
            },
            $buffer
        );
    });
?>