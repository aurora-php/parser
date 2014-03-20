<?php

/*
 * This file is part of the 'org.octris.core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace org\octris\core\parser {
    /**
     * Class for defining a parser grammar.
     *
     * @octdoc      c:parser/grammar
     * @copyright   copyright (c) 2014 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class grammar
    /**/
    {
        /**
         * Unknown token.
         *
         * @octdoc  d:grammar/T_UNKNOWN
         * @type    int
         */
        const T_UNKNOWN = 0;
        /**/
        
        /**
         * ID of initial rule.
         *
         * @octdoc  p:grammar/$initial
         * @type    int|string|null
         */
        protected $initial = null;
        /**/
        
        /**
         * Grammar rules.
         *
         * @octdoc  p:grammar/$rules
         * @type    array
         */
        protected $rules = [];
        /**/
        
        /**
         * Events for tokens.
         *
         * @octdoc  p:grammar/$events
         * @type    array
         */
        protected $events = [];
        /**/
        
        /**
         * Registered tokens.
         *
         * @octdoc  p:grammar/$tokens
         * @type    array
         */
        protected $tokens = array();
        /**/
        
        /**
         * Constructor.
         *
         * @octdoc  m:grammar/__construct
         */
        public function __construct()
        /**/
        {
        }
        
        /**
         * Add a rule to the grammar.
         *
         * @octdoc  m:grammar/addRule
         * @param   int|string          $id                 Token identifier to apply the rule for.
         * @param   array               $rule               Grammar rule.
         * @param   bool                $initial            Whether to set the rule as initial.
         */
        public function addRule($id, array $rule, $initial = false)
        /**/
        {
            if (isset($this->rules[$id])) {
                throw new \Exception('Rule is already defined!');
            }
            
            $this->rules[$id] = $rule;
            
            if ($initial) {
                $this->initial = $id;
            }
        }
        
        /**
         * Add an event for a token.
         *
         * @octdoc  m:grammar/addEvent
         * @param   int                 $id                 Token identifier.
         * @param   callable            $cb                 Callback to call if the token occurs.
         */
        public function addEvent($id, callable $cb)
        /**/
        {
            if (!isset($this->events[$id])) {
                $this->events[$id] = [];
            }
            
            $this->events[$id][] = $cb;
        }
        
        /**
         * Return list of defined tokens.
         *
         * @octdoc  m:grammar/getTokens
         * @return  array                                   Defined tokens.
         */
        public function getTokens()
        /**/
        {
            return $this->tokens;
        }

        /**
         * Return names of tokens. Will only work, if tokens are defined using class 'constants'.
         *
         * @octdoc  m:grammar/getTokenNames
         * @return  array                                   Names of defined tokens.
         */
        public function getTokenNames()
        /**/
        {
            return array_flip((new \ReflectionClass($this))->getConstants());
        }
        
        /**
         * Add a token to the registry.
         *
         * @octdoc  m:grammar/addToken
         * @param   string          $name                   Name of token.
         * @param   string          $regexp                 Regular expression for parser to match token.
         */
        public function addToken($name, $regexp)
        /**/
        {
            $this->tokens[$name] = $regexp;
        }
        
        /**
         * Return the EBNF for the defined grammar.
         *
         * @octdoc  m:grammar/getEBNF
         * @return  string                                  The EBNF.
         */
        public function getEBNF()
        /**/
        {
            $glue = array(
                '$concatenation' => array('', ' , ', ''),
                '$alternation'   => array('( ', ' | ', ' )'),
                '$repeat'        => array('{ ', '', ' }'),
                '$option'        => array('[ ', '', ' ]')
            );
    
            $render = function($rule) use ($glue, &$render) {
                if (is_array($rule)) {
                    $type = key($rule);

                    foreach ($rule[$type] as &$_rule) {
                        $_rule = $render($_rule);
                    }

                    $return = $glue[$type][0] . 
                              implode($glue[$type][1], $rule[$type]) .
                              $glue[$type][2];
                } else {
                    $return = $rule;
                }
        
                return $return;
            };
    
            $return = '';
    
            foreach ($this->rules as $name => $rule) {
                $return .= $name . ' = ' . $render($rule) . " ;\n";
            }
    
            return $return;
        }

        /**
         * Analyze / validate token stream. If the token stream is invalid, the second, optional, parameter
         * will contain the expected token.
         *
         * @octdoc  m:grammar/analyze
         * @param   array               $tokens             Token stream to analyze.
         * @param   array               &$expected          Expected token if an error occured.
         * @return  bool                                    Returns true if token stream is valid compared to the defined grammar.
         */
        public function analyze($tokens, &$expected = null)
        /**/
        {
            $expected = [];
            $pos      = 0;
            $error    = false;

            $v = function($rule) use ($tokens, &$pos, &$v, &$expected, &$error) {
                $valid = false;
    
                if (is_scalar($rule) && isset($this->rules[$rule])) {
                    // import rule
                    $rule = $this->rules[$rule];
                }
    
                if (is_array($rule)) {
                    $type = key($rule);
        
                    switch ($type) {
                        case '$concatenation':
                            $state = $pos;
                
                            foreach ($rule[$type] as $_rule) {
                                if (!($valid = $v($_rule))) {
                                    if (($error = ($error || ($pos - $state) > 0))) {
                                        return false;
                                    }
                                    break;
                                }
                            }

                            if (!$valid) {
                                // rule did not match, restore position in token stream
                                $pos   = $state;
                                $valid = false;
                            }
                            break;
                        case '$alternation':
                            $state = $pos;
                
                            foreach ($rule[$type] as $_rule) {
                                if (($valid = $v($_rule)) || $error) {
                                    // if ($error) return false;
                                    break;
                                }
                            }                

                            if (!$valid) {
                                // rule did not match, restore position in token stream
                                $pos   = $state;
                                $valid = false;
                            }
                            break;
                        case '$option':
                            $state = $pos;
                
                            foreach ($rule[$type] as $_rule) {
                                if (($valid = $v($_rule)) || $error) {
                                    if ($error) return false;
                                    break;
                                }
                            }
                
                            if (!$valid) {
                                // rule did not match, restore position in token stream
                                $pos   = $state;
                                $valid = true;
                            }
                            break;
                        case '$repeat':
                            do {
                                $state = $pos;
                
                                foreach ($rule[$type] as $_rule) {
                                    if (($valid = $v($_rule)) || $error) {
                                        if ($error) return false;
                                        break;
                                    }
                                }
                            } while($valid);
                
                            if (!$valid) {
                                // rule did not match, restore position in token stream
                                $pos   = $state;
                                $valid = true;
                            }
                            break;
                    }
                } elseif (($valid = isset($tokens[$pos]))) {
                    $token = $tokens[$pos];
        
                    if (($valid = ($token['token'] == $rule))) {
                        ++$pos;
                        $expected = [];
                    } else {
                        $expected[] = $rule;
                    }
                }
    
                return (!$error ? $valid : false);
            };

            if (!is_null($this->initial)) {
                $valid = $v($this->rules[$this->initial]);
            } else {
                // no initial rule, build one
                $valid = $v(['$alternation' => array_keys($this->rules)]);
            }

            if ($error) {
                $valid = false;
                dprint('error: %d, valid: %d', $error, $valid);
                ddump($expected);
            }

            $expected = array_unique($expected);

            return $valid;
        }
    }
}
