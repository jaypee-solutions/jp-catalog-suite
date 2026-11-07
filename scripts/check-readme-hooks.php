<?php
$arguments = $argv;
array_shift($arguments);
$readme = array_shift($arguments) ?? 'README.md';
$roots = $arguments ?: ['includes', 'templates', 'uninstall.php', 'jp-catalog-suite.php'];
$codeHooks = [];
foreach ($roots as $root) {
    if (is_file($root)) {
        $files = [$root];
    } elseif (is_dir($root)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        $files = [];
        foreach ($iterator as $entry) if ($entry->isFile() && $entry->getExtension() === 'php') $files[] = $entry->getPathname();
    } else {
        continue;
    }
    foreach ($files as $file) {
        $source = file_get_contents($file);
        preg_match_all('/\b(?:apply_filters|do_action|add_action|add_filter)\s*\(\s*[\'\"](jps_cs_[A-Za-z0-9_]+)/', $source, $matches);
        foreach ($matches[1] as $hook) $codeHooks[$hook] = true;
    }
}
$readmeSource = file_get_contents($readme);
preg_match_all('/^\|[^\n]*`(jps_cs_[A-Za-z0-9_]+)`/m', $readmeSource, $matches);
$documentedHooks = array_fill_keys($matches[1], true);
$failed = false;
foreach (array_diff_key($codeHooks, $documentedHooks) as $hook => $_) {
    fwrite(STDERR, "$readme: undocumented hook $hook\n");
    $failed = true;
}
foreach (array_diff_key($documentedHooks, $codeHooks) as $hook => $_) {
    fwrite(STDERR, "$readme: stale documented hook $hook\n");
    $failed = true;
}
exit($failed ? 1 : 0);
