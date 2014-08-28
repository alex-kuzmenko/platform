<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Form\Type\ChoiceType;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;

class ChoiceTypeTest extends AbstractConfigTypeTestCase
{
    /** @var ChoiceType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new ChoiceType(new ConfigTypeHelper($this->configManager));
    }

    /**
     * @dataProvider setDefaultOptionsProvider
     */
    public function testSetDefaultOptions(
        $configId,
        $hasConfig,
        $immutable,
        array $options,
        array $expectedOptions
    ) {
        $this->doTestSetDefaultOptions(
            $this->type,
            $configId,
            $hasConfig,
            $immutable,
            $options,
            $expectedOptions
        );
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_config_choice',
            $this->type->getName()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            'choice',
            $this->type->getParent()
        );
    }
}
