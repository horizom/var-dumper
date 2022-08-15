<?php

use Horizom\VarDumper\VarDumper;

require __DIR__ . '/../vendor/autoload.php';

if (isset($_GET['mode'])) {
    $htmlMode = ($_GET['mode'] !== 'text');

    require __DIR__ . '/example.class.php';

    VarDumper::config('showPrivateMembers', true);
    VarDumper::config('showIteratorContents', true);
    VarDumper::config('showUrls', true);
    VarDumper::config('showBacktrace', false);

    /**
     * Test class
     */
    final class Today extends \Tests\ClassTest
    {
    }

    /**
     * Test function
     *
     * @param   $test  Test argument
     * @return  void   Nothing
     */
    function today($test)
    {
    }

    $closedCurlRes = curl_init();
    curl_close($closedCurlRes);

    $array = array(
        'hèllo world'                       => '(͡°͜ʖ͡°)',
        'empty string'                      => '',
        'multiline string'                  => "first line and some padding   \nsecond line",
        'infinity'                          => INF,
        'regular expression (pcre)'         => '/^([0-9a-zA-Z]([-\.\w]*[0-9a-zA-Z])*@([0-9a-zA-Z][-\w]*[0-9a-zA-Z]\.)+[a-zA-Z]{2,9})$/',
        'multi'                             => array(1, 2, 3, array(4, 5, 6), 'FUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUU'),
        'matching class'                    => 'DateTime',
        'matching file'                     => 'file.txt',
        'incomplete object'                 => unserialize('O:3:"Foo":1:{s:3:"bar";i:5;}'),
        'empty object'                      => new \StdClass(),
        'closed CURL resource'              => $closedCurlRes,
        'matching date/file/function/class' => 'today',
        'url'                               => 'http://google.com',
    );

    $array['reference to self'] = &$array;

    $obj = new \Tests\ClassTest(array('foo', 'bar'), $array);

    if ($htmlMode) {
        dump(true, false, 'I can haz a 강남스타일 string', '1492-10-14 04:20:00 America/Nassau', null, 4.20);
        dump(array(), $array, serialize(array('A', 'serialized', 'string')));
        dump(fopen('php://stdin', 'r'), function ($x, $d) {
        });
        dump(new \DateTimeZone('Pacific/Honolulu'));
        dump($obj, new VarDumper());
    } else {

        dump_text(true, false, 'I can haz a 강남스타일 string', '1492-10-14 04:20:00 America/Nassau', null, 17, 4.20);
        dump_text(array(), $array, serialize(array('A', 'serialized', 'string')));
        dump_text(fopen('php://stdin', 'r'), function ($x, $d) {
        });
        dump_text(new \DateTimeZone('Pacific/Honolulu'));
        dump_text($obj, new VarDumper());
    }

    exit(0);
}
?>

<!DOCTYPE HTML>
<html>

<head>
    <title>REF by digitalnature</title>
    <style>
        body {
            font: 40px "Helvetica Neue", Helvetica, Arial, sans-serif;
            text-align: center;
            color: #ccc;
        }

        a {
            color: #2183cf;
            text-decoration: none;
        }

        a:hover {
            background: #2183cf;
            color: #fff;
        }

        h1 {
            font-size: 400%;
        }

        h3 {
            border-top: 1px solid #ccc;
            padding-top: 20px;
        }
    </style>
</head>

<body>
    <h1><a href="https://github.com/digitalnature/php-ref">REF</a></h1>
    <h2><a href="index.php?mode=html">HTML output</a> ~ <a href="index.php?mode=text">TEXT output</a></h2>
    <h3> created by <a href="http://digitalnature.eu/">digitalnature</digitalnature>
    </h3>
</body>

</html>