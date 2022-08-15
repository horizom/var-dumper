<?php

use Horizom\VarDumper\VarDumper;

/**
 * Shortcut to ref, HTML mode
 *
 * @param   mixed $args
 * @return  void|string
 */
function dump()
{

    // arguments passed to this function
    $args = func_get_args();

    // options (operators) gathered by the expression parser;
    // this variable gets passed as reference to getInputExpressions(), which will store the operators in it
    $options = array();

    // names of the arguments that were passed to this function
    $expressions = VarDumper::getInputExpressions($options);
    $capture = in_array('@', $options, true);

    // something went wrong while trying to parse the source expressions?
    // if so, silently ignore this part and leave out the expression info
    if (func_num_args() !== count($expressions)) {
        $expressions = null;
    }

    // use HTML formatter only if we're not in CLI mode, or if return was requested
    $format = (php_sapi_name() !== 'cli') || $capture ? 'html' : 'cliText';

    // IE goes funky if there's no doctype
    if (!$capture && ($format === 'html') && !headers_sent() && (!ob_get_level() || ini_get('output_buffering'))) {
        print '<!DOCTYPE HTML><html><head><title>REF</title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /></head><body>';
    }

    $ref = new VarDumper($format);

    if ($capture) {
        ob_start();
    }

    foreach ($args as $index => $arg) {
        $ref->query($arg, $expressions ? $expressions[$index] : null);
    }

    // return the results if this function was called with the error suppression operator
    if ($capture) {
        return ob_get_clean();
    }

    // stop the script if this function was called with the bitwise not operator
    if (in_array('~', $options, true) && ($format === 'html')) {
        print '</body></html>';
        exit(0);
    }
}

/**
 * Shortcut to ref, plain text mode
 *
 * @param   mixed $args
 * @return  void|string
 */
function dump_text()
{
    $args        = func_get_args();
    $options     = array();
    $expressions = VarDumper::getInputExpressions($options);
    $capture     = in_array('@', $options, true);
    $ref         = new VarDumper((php_sapi_name() !== 'cli') || $capture ? 'text' : 'cliText');

    if (func_num_args() !== count($expressions)) {
        $expressions = null;
    }

    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8');
    }

    if ($capture) {
        ob_start();
    }

    foreach ($args as $index => $arg) {
        $ref->query($arg, $expressions ? $expressions[$index] : null);
    }

    if ($capture) {
        return ob_get_clean();
    }

    if (in_array('~', $options, true)) {
        exit(0);
    }
}
