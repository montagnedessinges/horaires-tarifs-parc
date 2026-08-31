<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php tools/prepare-production.php <build-directory>\n");
    exit(1);
}

$root = realpath($argv[1]);
if ($root === false || !is_dir($root)) {
    fwrite(STDERR, "Build directory not found.\n");
    exit(1);
}

function strip_php_comments($code, $file) {
    $tokens = token_get_all($code);
    $out = '';
    foreach ($tokens as $token) {
        if (!is_array($token)) {
            $out .= $token;
            continue;
        }
        list($id, $text) = $token;
        if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
            $keep_plugin_header = basename($file) === 'horaires-tarifs-parc.php' && strpos($text, 'Plugin Name:') !== false;
            if ($keep_plugin_header) {
                $out .= $text;
            } else {
                $out .= strpos($text, "\n") !== false ? "\n" : ' ';
            }
            continue;
        }
        $out .= $text;
    }
    // Le contrôle de sécurité s'applique avant retrait des commentaires : garantir
    // que le paquet conserve exactement les mêmes tokens exécutables PHP.
    $executable_tokens = static function ($source) {
        $result = array();
        foreach (token_get_all($source, TOKEN_PARSE) as $token) {
            if (is_array($token) && in_array($token[0], array(T_COMMENT, T_DOC_COMMENT, T_WHITESPACE), true)) continue;
            $result[] = is_array($token) ? array($token[0], $token[1]) : $token;
        }
        return $result;
    };
    if ($executable_tokens($code) !== $executable_tokens($out)) {
        throw new RuntimeException('Production cleanup changed PHP executable tokens: ' . $file);
    }
    return $out;
}

function strip_css_comments($code) {
    $length = strlen($code);
    $out = '';
    $quote = null;
    for ($i = 0; $i < $length; $i++) {
        $c = $code[$i];
        $n = $i + 1 < $length ? $code[$i + 1] : '';
        if ($quote !== null) {
            $out .= $c;
            if ($c === '\\' && $i + 1 < $length) {
                $out .= $code[++$i];
                continue;
            }
            if ($c === $quote) $quote = null;
            continue;
        }
        if ($c === '"' || $c === "'") {
            $quote = $c;
            $out .= $c;
            continue;
        }
        if ($c === '/' && $n === '*') {
            $i += 2;
            while ($i < $length && !($code[$i] === '*' && $i + 1 < $length && $code[$i + 1] === '/')) $i++;
            if ($i < $length) $i++;
            $out .= ' ';
            continue;
        }
        $out .= $c;
    }
    return $out;
}

function slash_starts_regex($previous) {
    if ($previous === '') return true;
    return strpos('([{:;,=!?&|+-*%^~<>', $previous) !== false;
}

function strip_js_comments($code) {
    $length = strlen($code);
    $out = '';
    $state = 'normal';
    $previous = '';
    for ($i = 0; $i < $length; $i++) {
        $c = $code[$i];
        $n = $i + 1 < $length ? $code[$i + 1] : '';

        if ($state === 'single' || $state === 'double' || $state === 'template') {
            $out .= $c;
            if ($c === '\\' && $i + 1 < $length) {
                $out .= $code[++$i];
                continue;
            }
            if (($state === 'single' && $c === "'") || ($state === 'double' && $c === '"') || ($state === 'template' && $c === '`')) {
                $state = 'normal';
                $previous = $c;
            }
            continue;
        }

        if ($state === 'regex') {
            $out .= $c;
            if ($c === '\\' && $i + 1 < $length) {
                $out .= $code[++$i];
                continue;
            }
            if ($c === '[') {
                $state = 'regex-class';
                continue;
            }
            if ($c === '/') {
                while ($i + 1 < $length && preg_match('/[a-z]/i', $code[$i + 1])) $out .= $code[++$i];
                $state = 'normal';
                $previous = '/';
            }
            continue;
        }

        if ($state === 'regex-class') {
            $out .= $c;
            if ($c === '\\' && $i + 1 < $length) {
                $out .= $code[++$i];
                continue;
            }
            if ($c === ']') $state = 'regex';
            continue;
        }

        if ($c === "'") {
            $state = 'single';
            $out .= $c;
            continue;
        }
        if ($c === '"') {
            $state = 'double';
            $out .= $c;
            continue;
        }
        if ($c === '`') {
            $state = 'template';
            $out .= $c;
            continue;
        }
        if ($c === '/' && $n === '/') {
            $i += 2;
            while ($i < $length && $code[$i] !== "\n" && $code[$i] !== "\r") $i++;
            if ($i < $length) $out .= $code[$i];
            continue;
        }
        if ($c === '/' && $n === '*') {
            $i += 2;
            while ($i < $length && !($code[$i] === '*' && $i + 1 < $length && $code[$i + 1] === '/')) $i++;
            if ($i < $length) $i++;
            $out .= ' ';
            continue;
        }
        if ($c === '/' && slash_starts_regex($previous)) {
            $state = 'regex';
            $out .= $c;
            continue;
        }

        $out .= $c;
        if (!ctype_space($c)) $previous = $c;
    }
    return $out;
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getPathname();
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($extension, array('php', 'js', 'css'), true)) continue;

    $code = file_get_contents($path);
    if ($code === false) {
        fwrite(STDERR, "Unable to read {$path}\n");
        exit(1);
    }

    if ($extension === 'php') $clean = strip_php_comments($code, $path);
    elseif ($extension === 'css') $clean = strip_css_comments($code);
    else $clean = strip_js_comments($code);

    if (file_put_contents($path, $clean) === false) {
        fwrite(STDERR, "Unable to write {$path}\n");
        exit(1);
    }
}

fwrite(STDOUT, "Production sources cleaned.\n");
