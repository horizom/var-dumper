<?php

namespace Horizom\VarDumper\Formatter;

use Horizom\VarDumper\VarDumper;

class HtmlFormatter extends Formatter
{
    /**
     * Actual output
     *
     * @var  string
     */
    protected $out = '';

    /**
     * Tracks current nesting level
     *
     * @var  int
     */
    protected $level = 0;

    /**
     * Stores tooltip content for all entries
     *
     * To avoid having duplicate tooltip data in the HTML, we generate them once,
     * and use references (the Q index) to pull data when required;
     * this improves performance significantly
     *
     * @var  array
     */
    protected $tips = array();

    /**
     * Used to cache output to speed up processing.
     *
     * Contains hashes as keys and string offsets as values.
     * Cached objects will not be processed again in the same query
     *
     * @var  array
     */
    protected $cache = array();

    /**
     * Map of used HTML tag and attributes
     *
     * @var string
     */
    protected $def = array();

    /**
     * Instance counter
     *
     * @var  int
     */
    protected static $counter = 0;

    /**
     * Tracks style/jscript inclusion state
     *
     * @var  bool
     */
    protected static $didAssets = false;

    public function __construct()
    {
        if (VarDumper::config('validHtml')) {
            $this->def = array(
                'base'   => 'span',
                'tip'    => 'div',
                'cell'   => 'data-cell',
                'table'  => 'data-table',
                'row'    => 'data-row',
                'group'  => 'data-group',
                'gLabel' => 'data-gLabel',
                'match'  => 'data-match',
                'tipRef' => 'data-tip',
            );
        } else {
            $this->def = array(
                'base'   => 'r',
                'tip'    => 't',
                'cell'   => 'c',
                'table'  => 't',
                'row'    => 'r',
                'group'  => 'g',
                'gLabel' => 'gl',
                'match'  => 'm',
                'tipRef' => 'h',
            );
        }
    }

    public function flush()
    {
        print $this->out;
        $this->out   = '';
        $this->cache = array();
        $this->tips  = array();
    }

    public function didCache($id)
    {
        if (!isset($this->cache[$id])) {
            $this->cache[$id] = array();
            $this->cache[$id][] = strlen($this->out);
            return false;
        }

        if (!isset($this->cache[$id][1])) {
            $this->cache[$id][0] = strlen($this->out);
            return false;
        }

        $this->out .= substr($this->out, $this->cache[$id][0], $this->cache[$id][1]);
        return true;
    }

    public function cacheLock($id)
    {
        $this->cache[$id][] = strlen($this->out) - $this->cache[$id][0];
    }

    public function sep($label = ' ')
    {
        $this->out .= $label !== ' ' ? '<i>' . static::escape($label) . '</i>' : $label;
    }

    public function text($type, $text = null, $meta = null, $uri = null)
    {
        if (!is_array($type)) {
            $type = (array)$type;
        }

        $tip  = '';
        $text = ($text !== null) ? static::escape($text) : static::escape($type[0]);

        if (in_array('special', $type)) {
            $text = strtr($text, array(
                "\r" => '<i>\r</i>',     // carriage return
                "\t" => '<i>\t</i>',     // horizontal tab
                "\n" => '<i>\n</i>',     // linefeed (new line)
                "\v" => '<i>\v</i>',     // vertical tab
                "\e" => '<i>\e</i>',     // escape
                "\f" => '<i>\f</i>',     // form feed
                "\0" => '<i>\0</i>',
            ));
        }

        // generate tooltip reference (probably the slowest part of the code ;)
        if ($meta !== null) {
            $tipIdx = array_search($meta, $this->tips, true);

            if ($tipIdx === false) {
                $tipIdx = array_push($this->tips, $meta) - 1;
            }

            $tip = " {$this->def['tipRef']}=\"{$tipIdx}\"";
        }

        // wrap text in a link?
        if ($uri !== null) {
            $text = '<a href="' . $uri . '" target="_blank">' . $text . '</a>';
        }

        $typeStr = '';

        foreach ($type as $part) {
            $typeStr .= " data-{$part}";
        }

        $this->out .= "<{$this->def['base']}{$typeStr}{$tip}>{$text}</{$this->def['base']}>";
    }

    public function startContain($type, $label = false)
    {
        if (!is_array($type)) {
            $type = (array)$type;
        }

        if ($label) {
            $this->out .= '<br>';
        }

        $typeStr = '';
        foreach ($type as $part) {
            $typeStr .= " data-{$part}";
        }

        $this->out .= "<{$this->def['base']}{$typeStr}>";

        if ($label) {
            $this->out .= "<{$this->def['base']} {$this->def['match']}>{$type[0]}</{$this->def['base']}>";
        }
    }

    public function endContain()
    {
        $this->out .= "</{$this->def['base']}>";
    }

    public function emptyGroup($prefix = '')
    {

        if ($prefix !== '') {
            $prefix = "<{$this->def['base']} {$this->def['gLabel']}>" . static::escape($prefix) . "</{$this->def['base']}>";
        }

        $this->out .= "<i>(</i>{$prefix}<i>)</i>";
    }


    public function startGroup($prefix = '')
    {

        $maxDepth = VarDumper::config('maxDepth');

        if (($maxDepth > 0) && (($this->level + 1) > $maxDepth)) {
            $this->emptyGroup('...');
            return false;
        }

        $this->level++;

        $expLvl = VarDumper::config('expLvl');
        $exp = ($expLvl < 0) || (($expLvl > 0) && ($this->level <= $expLvl)) ? ' data-exp' : '';

        if ($prefix !== '') {
            $prefix = "<{$this->def['base']} {$this->def['gLabel']}>" . static::escape($prefix) . "</{$this->def['base']}>";
        }

        $this->out .= "<i>(</i>{$prefix}<{$this->def['base']} data-toggle{$exp}></{$this->def['base']}><{$this->def['base']} {$this->def['group']}><{$this->def['base']} {$this->def['table']}>";

        return true;
    }

    public function endGroup()
    {
        $this->out .= "</{$this->def['base']}></{$this->def['base']}><i>)</i>";
        $this->level--;
    }

    public function sectionTitle($title)
    {
        $this->out .= "</{$this->def['base']}><{$this->def['base']} data-tHead>{$title}</{$this->def['base']}><{$this->def['base']} {$this->def['table']}>";
    }

    public function startRow()
    {
        $this->out .= "<{$this->def['base']} {$this->def['row']}><{$this->def['base']} {$this->def['cell']}>";
    }

    public function endRow()
    {
        $this->out .= "</{$this->def['base']}></{$this->def['base']}>";
    }

    public function colDiv($padLen = null)
    {
        $this->out .= "</{$this->def['base']}><{$this->def['base']} {$this->def['cell']}>";
    }

    public function bubbles(array $items)
    {
        if (!$items) {
            return;
        }

        $this->out .= "<{$this->def['base']} data-mod>";

        foreach ($items as $info) {
            $this->out .= $this->text('mod-' . strtolower($info[1]), $info[0], $info[1]);
        }

        $this->out .= "</{$this->def['base']}>";
    }

    public function startExp()
    {
        $this->out .= "<{$this->def['base']} data-input>";
    }

    public function endExp()
    {
        if (VarDumper::config('showBacktrace') && ($trace = VarDumper::getBacktrace())) {
            $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
            $path = strpos($trace['file'], $docRoot) !== 0 ? $trace['file'] : ltrim(str_replace($docRoot, '', $trace['file']), '/');
            $this->out .= "<{$this->def['base']} data-backtrace>{$path}:{$trace['line']}</{$this->def['base']}>";
        }

        $this->out .= "</{$this->def['base']}><{$this->def['base']} data-output>";
    }

    public function startRoot()
    {
        $this->out .= '<!-- ref#' . ++static::$counter . ' --><div>' . static::getAssets() . '<div class="ref">';
    }

    public function endRoot()
    {
        $this->out .= "</{$this->def['base']}>";

        // process tooltips
        $tipHtml = '';

        foreach ($this->tips as $idx => $meta) {
            $tip = '';
            if (!is_array($meta)) {
                $meta = array('title' => $meta);
            }

            $meta += array(
                'title'       => '',
                'left'        => '',
                'description' => '',
                'tags'        => array(),
                'sub'         => array(),
            );

            $meta = static::escape($meta);
            $cols = array();

            if ($meta['left']) {
                $cols[] = "<{$this->def['base']} {$this->def['cell']} data-varType>{$meta['left']}</{$this->def['base']}>";
            }

            $title = $meta['title'] ?       "<{$this->def['base']} data-title>{$meta['title']}</{$this->def['base']}>"       : '';
            $desc  = $meta['description'] ? "<{$this->def['base']} data-desc>{$meta['description']}</{$this->def['base']}>"  : '';
            $tags  = '';

            foreach ($meta['tags'] as $tag => $values) {
                foreach ($values as $value) {
                    if ($tag === 'param') {
                        $value[0] = "{$value[0]} {$value[1]}";
                        unset($value[1]);
                    }

                    $value  = is_array($value) ? implode("</{$this->def['base']}><{$this->def['base']} {$this->def['cell']}>", $value) : $value;
                    $tags  .= "<{$this->def['base']} {$this->def['row']}><{$this->def['base']} {$this->def['cell']}>@{$tag}</{$this->def['base']}><{$this->def['base']} {$this->def['cell']}>{$value}</{$this->def['base']}></{$this->def['base']}>";
                }
            }

            if ($tags) {
                $tags = "<{$this->def['base']} {$this->def['table']}>{$tags}</{$this->def['base']}>";
            }

            if ($title || $desc || $tags) {
                $cols[] = "<{$this->def['base']} {$this->def['cell']}>{$title}{$desc}{$tags}</{$this->def['base']}>";
            }

            if ($cols) {
                $tip = "<{$this->def['base']} {$this->def['row']}>" . implode('', $cols) . "</{$this->def['base']}>";
            }

            $sub = '';

            foreach ($meta['sub'] as $line) {
                $sub .= "<{$this->def['base']} {$this->def['row']}><{$this->def['base']} {$this->def['cell']}>" . implode("</{$this->def['base']}><{$this->def['base']} {$this->def['cell']}>", $line) . "</{$this->def['base']}></{$this->def['base']}>";
            }

            if ($sub) {
                $tip .= "<{$this->def['base']} {$this->def['row']}><{$this->def['base']} {$this->def['cell']} data-sub><{$this->def['base']} {$this->def['table']}>{$sub}</{$this->def['base']}></{$this->def['base']}></{$this->def['base']}>";
            }

            if ($tip) {
                $this->out .= "<{$this->def['tip']}>{$tip}</{$this->def['tip']}>";
            }
        }

        if (($timeout = VarDumper::getTimeoutPoint()) > 0) {
            $this->out .= sprintf("<{$this->def['base']} data-error>Listing incomplete. Timed-out after %4.2fs</{$this->def['base']}>", $timeout);
        }

        $this->out .= '</div></div><!-- /ref#' . static::$counter . ' -->';
    }

    /**
     * Get styles and javascript (only generated for the 1st call)
     *
     * @return  string
     */
    public static function getAssets()
    {

        // first call? include styles and javascript
        if (static::$didAssets) {
            return '';
        }

        ob_start();

        if (VarDumper::config('stylePath') !== false) {
?>
            <style>
                <?php readfile(str_replace('{:dir}', dirname(__DIR__), VarDumper::config('stylePath'))); ?>
            </style>
        <?php
        }

        if (VarDumper::config('scriptPath') !== false) {
        ?>
            <script>
                <?php readfile(str_replace('{:dir}', dirname(__DIR__), VarDumper::config('scriptPath'))); ?>
            </script>
<?php
        }

        // normalize space and remove comments
        $output = preg_replace('/\s+/', ' ', trim(ob_get_clean()));
        $output = preg_replace('!/\*.*?\*/!s', '', $output);
        $output = preg_replace('/\n\s*\n/', "\n", $output);

        static::$didAssets = true;
        return $output;
    }

    /**
     * Escapes variable for HTML output
     *
     * @param   string|array $var
     * @return  string|array
     */
    protected static function escape($var)
    {
        return is_array($var) ? array_map(self::class . '::escape', $var) : htmlspecialchars($var ?? '', ENT_QUOTES);
    }
}
