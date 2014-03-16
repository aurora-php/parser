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
            'ifile'   => '',
            'iline'   => 0,
            'line'    => 0,
            'token'   => '',
            'payload' => null
        );
        /**/

        /**
         * Instance of grammar class.
         *
         * @octdoc  p:parser/$grammar
         * @type    \org\octris\core\parser\grammar|null
         */
        protected $grammar = null;
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
         * @param   string      $ifile      Internal filename the error occured in.
         * @param   int         $iline      Internal line number the error occured in.
         * @param   int         $line       Line in template the error was triggered for.
         * @param   mixed       $token      Token that triggered the error.
         * @param   mixed       $payload    Optional additional information.
         */
        protected function setError($ifile, $iline, $line, $token, $payload = NULL)
        /**/
        {
            $this->last_error = array(
                'ifile'   => $ifile,
                'iline'   => $iline,
                'line'    => $line,
                'token'   => $token,
                'payload' => $payload
            );
        }

        /**
         * Return instance of grammar as it was specified for constructor.
         *
         * @octdoc  m:parser/getGrammar
         * @return  \org\octris\core\parser\grammar             Instance of grammar.
         */
        public function getGrammar()
        /**/
        {
            return $this->grammar;
        }

        /**
         * Return last occured error.
         *
         * @octdoc  m:parser/getLastError
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
                
                $this->setError(__FILE__, __LINE__, $line, 0, sprintf('parse error at "%s" in "%s"', $in, $mem));
                
                return false;
            }

            return $out;
        }        
    }
}
