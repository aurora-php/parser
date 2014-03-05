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
            
            $this->events[$id][] = $cb();
        }
        
        /**
         * Return list of defined tokens.
         *
         * @octdoc  m:grammar/getTokens
         * 
         */
        public function getTokens()
        /**/
        {
            return $this->tokens;
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

            $v = function($rule) use ($tokens, &$pos, &$v, &$expected) {
                $valid = false;
    
                if (is_array($rule)) {
                    $type = key($rule);
        
                    switch ($type) {
                        case '$concatenation':
                            foreach ($rule[$type] as $_rule) {
                                if (!($valid = $v($_rule))) {
                                    break;
                                }
                            }
                            break;
                        case '$alternation':
                            foreach ($rule[$type] as $_rule) {
                                if (($valid = $v($_rule))) {
                                    break;
                                }
                            }                
                            break;
                        case '$option':
                            $state = $pos;
                
                            foreach ($rule[$type] as $_rule) {
                                if (($valid = $v($_rule))) {
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
                                    if (($valid = $v($_rule))) {
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
                } else {
                    $token = $tokens[$pos++];
        
                    if (($valid = ($token == $rule))) {
                        $expected = [];
                    } else {
                        $expected[] = $rule;
                    }
        
                    printf("-> %s == %s: %d\n", $rule, $token, $valid);
                }
    
                return $valid;
            };

            $valid = $v($rules);

            $expected = array_unique($expected);

            return $valid;
        }
    }
}
