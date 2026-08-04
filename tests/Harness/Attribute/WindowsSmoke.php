<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Attribute;

/**
 * Marks a test as part of the Windows smoke subset: when the
 * MAKER_WINDOWS_SMOKE env var is set, every test WITHOUT this attribute is
 * skipped.
 *
 * Windows-specific bugs live in the maker's I/O layer (path separators,
 * CRLF, wizard console interaction, external binaries), not in the runtime
 * behavior of the generated PHP code. Linux already executes all of that on
 * three Symfony versions. The subset is therefore curated by GENERATION
 * MECHANISM, not by maker: each path-, template-, yaml-, binary- and
 * toolchain-touching code path is exercised end-to-end (the execution
 * contract stays fully enforced), and makers that only combine already
 * covered mechanisms are left out.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class WindowsSmoke
{
}
