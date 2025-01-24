<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use RuntimeException;

use function array_keys;
use function array_values;
use function is_null;
use function md5;
use function str_replace;
use function uniqid;

/**
 * @package AdrianSuter\Autoload\Override
 */
class CodeConverter
{
    private const ATTR_RESOLVED_NAME = 'resolvedName';

    protected Parser $parser;

    protected NodeTraverser $traverser;

    protected Standard $printer;

    protected NodeFinder $nodeFinder;

    /**
     * @param Parser|null $parser The PHP Parser.
     * @param NodeTraverser|null $traverser The PHP Node Traverser - make sure that the traverser has a CloningVisitor
     *                                      and a NameResolver visitor.
     * @param Standard|null $printer The PHP Printer.
     * @param NodeFinder|null $nodeFinder The PHP Node Finder.
     */
    public function __construct(
        ?Parser $parser = null,
        ?NodeTraverser $traverser = null,
        ?Standard $printer = null,
        ?NodeFinder $nodeFinder = null
    ) {
        $this->parser = $parser ?? (new ParserFactory())->createForNewestSupportedVersion();

        if (is_null($traverser)) {
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new CloningVisitor());
            $traverser->addVisitor(new NameResolver(null, ['replaceNodes' => false]));
        }
        $this->traverser = $traverser;

        $this->printer = $printer ?? new Standard();

        $this->nodeFinder = $nodeFinder ?? new NodeFinder();
    }

    /**
     * Convert the given source code.
     *
     * @param string $code The source code.
     * @param array<string, string> $functionCallMap The function call map.
     *
     * @return string
     */
    public function convert(string $code, array $functionCallMap): string
    {
        $oldStmts = $this->parser->parse($code);
        if (is_null($oldStmts)) {
            throw new RuntimeException('Code could not be parsed.');
        }

        $oldTokens = $this->parser->getTokens();

        $newStmts = $this->traverser->traverse($oldStmts);

        // Find function calls.
        $overridePlaceholders = [];
        $funcCalls = $this->nodeFinder->findInstanceOf($newStmts, FuncCall::class);
        foreach ($funcCalls as $funcCall) {
            /** @var FuncCall $funcCall */
            if (!$funcCall->name->hasAttribute(self::ATTR_RESOLVED_NAME)) {
                // This function call has no resolved fully qualified name.
                continue;
            }

            /** @var FullyQualified $resolvedName */
            $resolvedName = $funcCall->name->getAttribute(self::ATTR_RESOLVED_NAME);

            $resolvedNameCode = $resolvedName->toCodeString();
            if (isset($functionCallMap[$resolvedNameCode])) {
                // There is a function call map > Create a unique key.
                $key = uniqid(md5($resolvedNameCode), true);

                // Put the key into the overridePlaceholders array as at the end we need to
                // replace those keys with the corresponding target function call.
                $overridePlaceholders[$key] = $functionCallMap[$resolvedNameCode];

                // Replace the name to be the fully qualified name, i.e. the given unique key
                // (we will replace that at the end).
                $funcCall->name = new FullyQualified($key);
            }
        }

        // Print the source code.
        $code = $this->printer->printFormatPreserving($newStmts, $oldStmts, $oldTokens);

        // Return the source code if there are no override placeholders.
        if (empty($overridePlaceholders)) {
            return $code;
        }

        // Replace all override placeholders by their target function call.
        return str_replace(array_keys($overridePlaceholders), array_values($overridePlaceholders), $code);
    }
}
