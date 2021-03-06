<?php

/**
 * Test: Nette\DI\Compiler: service decorators.
 */

declare(strict_types=1);

use Nette\DI;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\Statement;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


interface Iface
{
	public const NAME = self::class;
}


class Service extends stdClass implements Iface
{
	public $setup;


	public function setup($a = null)
	{
		$this->setup[] = $a;
	}
}


$compiler = new DI\Compiler;
$compiler->addExtension('decorator', new Nette\DI\Extensions\DecoratorExtension);
$container = createContainer($compiler, '
decorator:
	stdClass:
		setup:
			- setup(Object)
		inject: yes

	Iface:
		setup:
			- setup(Iface::NAME)
			- setup
			- $a = 10
		tags: [Iface::NAME, tag: 1]

	spec1:
		setup:
		tags:

	spec2:

services:
	one:
		factory: Service
		tags: [a, tag: 2]
		setup:
			- setup(Service)
');


$builder = $compiler->getContainerBuilder();

Assert::same(
	['a' => true, 'tag' => 2, DI\Extensions\InjectExtension::TAG_INJECT => true, 'Iface' => true],
	$builder->getDefinition('one')->getTags()
);

Assert::true($builder->getDefinition('one')->getTag(DI\Extensions\InjectExtension::TAG_INJECT));

Assert::equal([
	new Statement([new Reference('self'), 'setup'], ['Service']),
	new Statement([new Reference('self'), 'setup'], ['Object']),
	new Statement([new Reference('self'), 'setup'], ['Iface']),
	new Statement([new Reference('self'), 'setup']),
	new Statement([new Reference('self'), '$a'], [10]),
], $builder->getDefinition('one')->getSetup());
