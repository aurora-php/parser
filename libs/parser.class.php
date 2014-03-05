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
     * @copyright   copyright (c) 2010-2014 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class parser
    /**/
    {
        /**
         * Last occured parser error.
         *
         * @octdoc  p:parser/$last_error
         * @type    array
         */
        protected $last_error = array(
            'type'    => '',
            'cline'   => 0,
            'line'    => 0,
            'token'   => $token,
            'payload' => null
        );
        /**/

        /**
         * Instance of grammar class.
         *
         * @octdoc  p:parser/$grammar
         * @type    \org\octris\core\parser\grammar|null
         */
        protected $grammar = null
        /**/

        /**
         * Tokens to ignore. Tokenizer will drop these tokens.
         *
         * @octdoc  p:parser/$ignore
         * @type    array
         */
        protected $ignore = array();
        /**/

        /**
         * Parser tokens.
         *
         * @octdoc  p:parser/$tokens
         * @type    array
         */
        protected $tokens = array();
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:parser/__construct
         * @param   \org\octris\core\parser\grammar     $grammar            Grammar to use for the parser.
         * @param   array                               $ignore             Optional tokens to ignore.
         */
        public function __construct(\org\octris\core\parser\grammar $grammar, array $ignore = array())
        /**/
        {
            $this->grammar = $grammar;
            $this->tokens  = $grammar->getTokens();
        }

        /**
         * Set parser error.
         *
         * @octdoc  m:parser/setError
         * @param   string      $type       Type of error to trigger.
         * @param   int         $cline      Line in compiler class error was triggered from.
         * @param   int         $line       Line in template the error was triggered for.
         * @param   int         $token      ID of token that triggered the error.
         * @param   mixed       $payload    Optional additional information. Either an array of expected.
         *                                  token IDs or an additional message to output.
         */
        protected function error($type, $cline, $line, $token, $payload = NULL)
        /**/
        {
            $this->last_error = array(
                'type'  => $type,
                'cline' => $cline,
                'line'  => $line,
                'token' => $token
            )
        }

        /**
         * Return last occured error.
         *
         * @octdoc  m:parset/getLastError
         */
        public function getLastError()
        /**/
        {
            return $this->last_error;
        }

        /**
         * String tokenizer.
         *
         * @octdoc  m:parser/tokenize
         * @param   string      $in         String to tokenize.
         * @param   int         $line       Optional line offset for error messages.
         * @return  array|bool              Tokens parsed from snippet or false if an error occured.
         */
        public function tokenize($in, $line = 1)
        /**/
        {
            $out = array();
            $mem = $in;

            while (strlen($in) > 0) {
                foreach ($this->tokens as $token => $regexp) {
                    if (preg_match('/^(' . $regexp . ')/i', $in, $m)) {
                        if (!in_array($token, $this->ignore)) {
                            // collect only tokens not in ignore-list
                            $out[] = array(
                                'token' => $token,
                                'value' => $m[1],
                                'line'  => $line
                            );
                        }

                        $in    = substr($in, strlen($m[1]));
                        $line += substr_count($m[1], "\n");
                        continue 2;
                    }
                }
                
                $this->setError(__FUNCTION__, __LINE__, $line, 0, sprintf('parse error at "%s" in "%s"', $in, $mem));
                
                return false;
            }

            return $out;
        }        
    }
}
