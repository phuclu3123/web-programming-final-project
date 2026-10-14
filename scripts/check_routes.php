<?php
// CLI-only static route integrity test. No database connection required.
declare(strict_types=1);
$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root) {
  if (!str_starts_with($class, 'App\\')) return;
  $parts = explode('\\', substr($class, 4));
  for ($i = 0; $i < count($parts) - 1; $i++) $parts[$i] = strtolower($parts[$i]);
  $path = $root . '/app/' . implode('/', $parts) . '.php';
  if (is_file($path)) require_once $path;
});
$code = file_get_contents($root . '/config/routes.php');
preg_match_all('/\\$router->(?:get|post)\\(\\x27([^\\x27]+)\\x27, \\[([^:]+)::class, \\x27([^\\x27]+)\\x27\\]\\);/', $code, $matches, PREG_SET_ORDER);
$issues = [];
foreach ($matches as $r) {
  $alias = trim($r[2]);
  $method = $r[3];
  // Resolve imports via unique short basename/alias.
  $imports = [];
  preg_match_all('/^use ([^;]+);/m', $code, $uses);
  foreach ($uses[1] as $u) {
    if (str_contains($u, ' as ')) [$class, $name] = explode(' as ', $u, 2);
    else {
      $class = $u;
      $name = substr($u, strrpos($u, '\\') + 1);
    }
    $imports[$name] = $class;
  }
  $class = $imports[$alias] ?? null;
  if (!$class || !class_exists($class) || !method_exists($class, $method)) $issues[] = "{$r[1]} -> $alias::$method";
}
if (count($matches) < 40) $issues[] = 'Insufficient routes parsed: ' . count($matches);
if ($issues) {
  fwrite(STDERR, "Broken routes:\n" . implode("\n", $issues) . "\n");
  exit(1);
}
echo 'PASS route reflection: ' . count($matches) . " routes\n";
