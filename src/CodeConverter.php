<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

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

class CodeConverter
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
     * @param Lexer|null         $lexer      The lexer.
     * @param Parser|null        $parser     The parser.
     * @param NodeTraverser|null $traverser  The traverser - make sure that the traverser has a CloningVisitor and a
     *                                       NameResolver visitor.
     * @param Standard|null      $printer    The printer.
     * @param NodeFinder|null    $nodeFinder The node finder.
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
     *
     * @return string
     */
    public function convert(string $code, array $functionCallMappings): string
    {
        $oldStmts = $this->parser->parse($code);
        $oldTokens = $this->lexer->getTokens();

        $overridePlaceholders = [];

        $newStmts = $this->traverser->traverse($oldStmts);
        $funcCalls = $this->nodeFinder->findInstanceOf($newStmts, FuncCall::class);
        foreach ($funcCalls as $funcCall) {
            /** @var FuncCall $funcCall */
            if ($funcCall->name->hasAttribute('resolvedName')) {
                /** @var FullyQualified $resolvedName */
                $resolvedName = $funcCall->name->getAttribute('resolvedName');
                $resolvedNameCode = $resolvedName->toCodeString();

                if (isset($functionCallMappings[$resolvedNameCode])) {
                    $k = uniqid(md5($resolvedNameCode), true);
                    $overridePlaceholders[$k] = $functionCallMappings[$resolvedNameCode];

                    $funcCall->name = new FullyQualified($k);
                }
            }
        }

        $code = $this->printer->printFormatPreserving($newStmts, $oldStmts, $oldTokens);

        return str_replace(array_keys($overridePlaceholders), array_values($overridePlaceholders), $code);
    }
}
