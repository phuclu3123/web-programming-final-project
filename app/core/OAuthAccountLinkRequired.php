<?php
declare(strict_types=1);
namespace App\Core;

/** Provider identity is valid but does not yet identify an internal account. */
final class OAuthAccountLinkRequired extends \RuntimeException {}
