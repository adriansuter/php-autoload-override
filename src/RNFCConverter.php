<?php
/**
 * Root Namespaced Function Call Override (https://github.com/adriansuter/php-rnfc-override)
 *
 * @license https://github.com/adriansuter/php-rnfc-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\RNFCOverride;

use PhpParser\Lexer;
use PhpParser\Lexer\Emulative;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;

class RNFCConverter
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var Lexer
     */
    protected $lexer;

    /**
     * @var NodeTraverser
     */
    protected $traverser;

    /**
     * @var Standard
     */
    protected $printer;

    /**
     * @var NodeFinder
     */
    protected $nodeFinder;

    /**
     * @param Lexer|null         $lexer
     * @param Parser|null        $parser
     * @param NodeTraverser|null $traverser
     * @param Standard|null      $printer
     * @param NodeFinder|null    $nodeFinder
     */
    public function __construct(
        ?Lexer $lexer = null,
        ?Parser $parser = null,
        ?NodeTraverser $traverser = null,
        ?Standard $printer = null,
        ?NodeFinder $nodeFinder = null
    ) {
        $this->lexer = $lexer ?? new Emulative([
                'usedAttributes' => ['comments', 'startLine', 'endLine', 'startTokenPos', 'endTokenPos'],
            ]);

        $this->parser = $parser ?? new Php7($this->lexer);

        if ($traverser === null) {
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new CloningVisitor());
            $traverser->addVisitor(new NameResolver(null, ['replaceNodes' => false]));
        }
        $this->traverser = $traverser;

        $this->printer = $printer ?? new Standard();

        $this->nodeFinder = $nodeFinder ?? new NodeFinder();
    }

    /**
     * Convert the given code.
     *
     * @param string $code
     * @param array  $functionCallMappings
     * @return string
     */
    public function convert(string $code, array $functionCallMappings): string
    {
        $oldStmts = $this->parser->parse($code);
        $oldTokens = $this->lexer->getTokens();

        $newStmts = $this->traverser->traverse($oldStmts);
        $funcCalls = $this->nodeFinder->findInstanceOf($newStmts, FuncCall::class);
        foreach ($funcCalls as $funcCall) {
            /** @var FuncCall $funcCall */
            if ($funcCall->name->hasAttribute('resolvedName')) {
                /** @var FullyQualified $resolvedName */
                $resolvedName = $funcCall->name->getAttribute('resolvedName');

                if (isset($functionCallMappings[$resolvedName->toCodeString()])) {
                    $funcCall->name = new FullyQualified(
                        $functionCallMappings[$resolvedName->toCodeString()]
                    );
                }
            }
        }

        return $this->printer->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
    }
}
