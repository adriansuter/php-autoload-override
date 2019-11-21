<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeDumper;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

$code = <<<'CODE'
<?php
class X {public function Y() {
    echo \date('d.m.Y', \time());
}}
CODE;

$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
try {
    $ast = $parser->parse($code);

//    $dumper = new NodeDumper();
//    echo $dumper->dump($ast) . "\n";

    $traverser = new NodeTraverser();
    $traverser->addVisitor(new NameResolver(null, ['replaceNodes' => false]));
    $traverser->addVisitor(new class extends NodeVisitorAbstract
    {
        public function enterNode(Node $node)
        {
            if ($node instanceof Node\Expr\FuncCall) {
                return new Node\Expr\MethodCall(
                    new Node\Expr\StaticCall(
                        new Node\Name\FullyQualified(['AdrianSuter', 'Autoload', 'Override', 'ClosureHandler']),
                        new Node\Name('getInstance')
                    ), new Node\Name($node->name . '_123'), $node->args, $node->getAttributes()
                );
            }

            return $node;
        }
    });
    $ast = $traverser->traverse($ast);

    $prettyPrinter = new Standard();
    echo $prettyPrinter->prettyPrintFile($ast);
} catch (Error $error) {
    echo "Parse error: {$error->getMessage()}\n";
    return;
}
