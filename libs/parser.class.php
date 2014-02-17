<?php

/*
 * This file is part of the 'org.octris.core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace org\octris\core {
    /**
     * General purpose parser.
     *
     * @octdoc      c:core/parser
     * @copyright   copyright (c) 2010-2011 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class parser
    /**/
    {
        /**
         * Default tokens, always required.
         * 
         * @octdoc  d:parser/T_...
         */
        const T_START      = 10000;
        const T_END        = 10001;
        const T_WHITESPACE = 10002;
        /**/

        /**
         * Names of tokens. This array gets build the first time the constructor is called.
         *
         * @octdoc  p:parser/$tokennames
         * @type    array
         * @abstract
         */
        protected static $tokennames = null;
        /**/

        /**
         * Regular expression patterns for parser tokens.
         *
         * @octdoc  p:parser/$tokens
         * @type    array
         * @abstract
         */
        protected static $tokens = array();
        /**/

        /**
         * Parser rules.
         *
         * @octdoc  p:parser/$rules
         * @type    array
         * @abstract
         */
        protected static $rules = array();
        /**/

        /**
         * Whether the tokenizer should ignore whitespaces.
         *
         * @octdoc  p:parser/$ignore_whitespace
         * @type    bool
         */
        protected static $ignore_whitespace = true;
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:parser/__construct
         */
        public function __construct()
        /**/
        {
            if (is_null(static::$tokennames)) {
                $class = new \ReflectionClass($this);
                static::$tokennames = array_flip($class->getConstants());
            }
        }

        /**
         * Return name of token.
         *
         * @octdoc  m:parser/getTokenName
         * @param   int     $token      ID of token.
         * @return  string              Name of token.
         */
        protected function getTokenName($token)
        /**/
        {
            return (isset(static::$tokennames[$token])
                    ? static::$tokennames[$token]
                    : 'T_UNKNOWN');
        }

        /**
         * Return names of multiple tokens.
         *
         * @octdoc  m:parser/getTokenNames
         * @param   array       $tokens     Array of token IDs.
         * @return  array                   Names of tokens.
         */
        protected function getTokenNames(array $tokens)
        /**/
        {
            $return = array();
            
            foreach ($tokens as $token) $return[] = $this->getTokenName($token);
            
            return $return;
        }
        
        /**
         * Retrieve next rule.
         *
         * @octdoc  m:parser/getNextRule
         * @param   mixed           $rule               Current rule.
         * @param   string          $token              Current token.
         * @param   array           $stack              Rule stack.
         * @return  mixed                               New rule or false, if no rule applies.
         */
        protected function getNextRule($rule, $token, &$stack)
        /**/
        {
            $return = false;
            
            if (is_array($rule) && array_key_exists($token, $rule)) {
                // valid token, because it's in current ruleset
                if (is_array($rule[$token])) {
                    // push current rule on stack and get child rule
                    $stack[] = $rule;
                    $return  = $rule[$token];
                } elseif (is_null($rule[$token])) {
                    // ruleset is null -> try to get it from parent rules
                    while (($return = array_pop($stack)) && !isset($return[$token]));

                    if (is_array($return)) {
                        $stack[] = $return;
                        $return  = $return[$token];
                    }
                } elseif (is_bool($rule[$token])) {
                    // ADDED THIS, because command line parser failed -- but template parser not!?
                    $return = $rule[$token];
                }
            }

            return $return;
        }
        
        /**
         * Trigger an error and halt execution.
         *
         * @octdoc  m:parser/error
         * @param   string      $type       Type of error to trigger.
         * @param   int         $cline      Line in compiler class error was triggered from.
         * @param   int         $line       Line in template the error was triggered for.
         * @param   int         $token      ID of token that triggered the error.
         * @param   mixed       $payload    Optional additional information. Either an array of expected token IDs or an additional message to output.
         */
        protected function error($type, $cline, $line, $token, $payload = NULL)
        /**/
        {
            printf("\n** ERROR: %s(%d) **\n", $type, $cline);
            printf("   line :    %d\n", $line);
            printf("   file:     %s\n", $this->filename);
            printf("   token:    %s\n", $this->getTokenName($token));
            
            if (is_array($payload)) {
                printf("   expected: %s\n", implode(', ', $this->getTokenNames(array_keys($payload))));
            } elseif (isset($payload)) {
                printf("   message:  %s\n", $payload);
            }
         
            die();
        }

        /**
         * Tokenizer converts template snippets to tokens.
         *
         * @octdoc  m:parser/tokenize
         * @param   string      $in         Template snippet to tokenize.
         * @param   int         $line       Line number of template the snippet was taken from.
         * @return  array                   Tokens parsed from snippet.
         */
        protected function tokenize($in, $line)
        /**/
        {
            $out = array();
            $in  = stripslashes($in);

            while (strlen($in) > 0) {
                foreach (static::$tokens as $token => $regexp) {
                    if (preg_match('/^(' . $regexp . ')/i', $in, $m)) {
                        if (!static::$ignore_whitespace || $token != static::T_WHITESPACE) {
                            // spaces between tokens are ignored
                            $out[] = array(
                                'token' => $token,
                                'value' => $m[1],
                                'file'  => $this->filename,
                                'line'  => $line
                            );
                        }

                        $in = substr($in, strlen($m[1]));
                        continue 2;
                    }
                }
                
                $this->error(__FUNCTION__, __LINE__, $line, 0, sprintf('parse error at "%s"', $in));
            }

            if (count($out) > 0) {
                array_unshift($out, array(
                    'token' => static::T_START,
                    'value' => '',
                    'file'  => $this->filename,
                    'line'  => $line
                ));
                array_push($out, array(
                    'token' => static::T_END,
                    'value' => '',
                    'file'  => $this->filename,
                    'line'  => $line
                ));
            }

            return $out;
        }
    }
}
