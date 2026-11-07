<?php
$root = $argv[1] ?? dirname(__DIR__);
$pot = file_get_contents($root . '/languages/jp-catalog-suite.pot');
$missing = [];
$files = [];
foreach (['includes', 'templates', 'blocks'] as $dir) {
    if (!is_dir($root . '/' . $dir)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir));
    foreach ($it as $file) if ($file->isFile() && $file->getExtension() === 'php') $files[] = $file->getPathname();
}
$check = static function (array $token) use (&$missing, $pot): void {
    $id = stripcslashes(substr($token[1], 1, -1));
    if (!preg_match('/^msgid "' . preg_quote(addcslashes($id, "\\\"\n\r\t"), '/') . '"$/m', $pot)) $missing[$id] = true;
};
foreach ($files as $file) {
    $tokens = token_get_all(file_get_contents($file));
    for ($i = 0; $i < count($tokens); $i++) {
        $token = $tokens[$i];
        if (!is_array($token) || $token[0] !== T_STRING || !in_array($token[1], ['__', '_e', 'esc_html__', '_x', '_n'], true)) continue;
        $j = $i + 1;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) $j++;
        if (($tokens[$j] ?? null) !== '(') continue;
        $j++;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) $j++;
        if (!is_array($tokens[$j] ?? null) || $tokens[$j][0] !== T_CONSTANT_ENCAPSED_STRING) continue;
        $check($tokens[$j]);
        if ($token[1] !== '_n') continue;
        do { $j++; } while (isset($tokens[$j]) && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true));
        if (($tokens[$j] ?? null) !== ',') continue;
        do { $j++; } while (isset($tokens[$j]) && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true));
        if (is_array($tokens[$j] ?? null) && $tokens[$j][0] === T_CONSTANT_ENCAPSED_STRING) $check($tokens[$j]);
    }
}
if ($missing) { foreach (array_keys($missing) as $id) fwrite(STDERR, "Missing POT msgid: $id\n"); exit(1); }
echo "POT coverage passed.\n";
