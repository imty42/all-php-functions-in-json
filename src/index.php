<?php

$index_page = 'http://php.net/manual/en/indexes.functions.php';

# cache for speed
$cache_file = __DIR__ . '/../cache/indexes.functions.php.html';
$page_cont = file_get_contents($cache_file);
#$page_cont = file_get_contents($index_page);

preg_match_all(
    '/<li><a href=["\'](function\.[^"\']*?\.php)["\'] class=["\'][^"\']+?["\']>' .
    '[^<>]+?<\/a>[^<>]*?<\/li>/is', $page_cont, $matches);

if (! isset($matches[1])) {
    exit("Faild parse the index page !\n");
}


# use dash doc local url for speed
$func_page_prefix = 'http://127.0.0.1:58256/Dash/aibcpgne/php.net/manual/en/';
$func_page_prefix_real = 'http://php.net/manual/en/';

$result = array();
foreach ($matches[1] as $func_page_name) {
//    $func_page_name = 'function.apcu-add.php';
//    $func_page_name = 'function.array-udiff.php';
    $func_page_url = $func_page_prefix . $func_page_name;
    $func_page_url = preg_replace('/\.php$/is', '.html', $func_page_url);
    $func_name = substr($func_page_name, 9, -4);

    $func_page_cont = file_get_contents($func_page_url);
    $func_page_url = str_replace($func_page_prefix, $func_page_prefix_real, $func_page_url);
    $func_page_url = preg_replace('/\.html$/is', '.php', $func_page_url);


    # eg: url name of preg_match is function.preg-match.html
    $func_name = str_replace('-', '_', $func_name);

    if (strpos($func_page_cont, '<div id="function.'. $func_name . '-refsynopsisdiv"') === false) {
        // if no warning included
        continue;
    }

    preg_match('/<p class="verinfo">\((.*?)\)<\/p>/is', $func_page_cont, $pmatches);
    $version_str = isset($pmatches[1]) ? $pmatches[1] : '';

    # looking for more info in http://php.net/manual/en/regexp.reference.assertions.php
    preg_match_all('/(?:(?<=\/div>)|(?<=\/h3>))\s*<div class="methodsynopsis dc-description">(.*?)(?=<\/div>)/is', $func_page_cont, $pmatches);
    $func_strs = isset($pmatches[1][0]) ? $pmatches[1] : array();
    foreach ($func_strs as &$func_str) {
        $func_str = preg_replace('/<[^<>]+?>/is', '', $func_str);
        $func_str = preg_replace('/\s{2,}/is', ' ', $func_str);
        $func_str = trim($func_str);
    }

    preg_match('/<div class="warning"><strong class="warning">Warning(.*?)<\/div>/is', $func_page_cont, $pmatches);
    $warning_str = isset($pmatches[1]) ? $pmatches[1] : '';
    $version_deprecated = '';
    $version_removed = '';
    $func_alternatives = array();
    if ($warning_str) {
        preg_match('/(?:(?:DEPRECATED<\/em> in)|(?: deprecated as of))\sPHP\s+([\d\.]+\d)/', $warning_str, $pmatches);
        $version_deprecated = isset($pmatches[1]) ? $pmatches[1] : '';

        preg_match('/(?:(?:REMOVED<\/em> in)|(?: removed as of))\sPHP\s+([\d\.]+\d)/', $warning_str, $pmatches);
        $version_removed = isset($pmatches[1]) ? $pmatches[1] : '';

        preg_match_all('/<a\s+href="[^"]*?"\s+class="function">([^\(\)]+?)\(/', $warning_str, $pmatches);
        $func_alternatives = isset($pmatches[1]) ? $pmatches[1] : array();
    }

    $data = array(
        "func_name" => $func_name,
        "func_page_url" => $func_page_url,
        "func_strs" => $func_strs,
        "version_string" => $version_str,
        "version_deprecated" => $version_deprecated,
        "version_removed" => $version_removed,
        "func_alternatives" => $func_alternatives
    );

    $result[] = $data;
}

$result_str = json_encode($result, JSON_PRETTY_PRINT);
file_put_contents('all_php_functions.json', $result_str);
