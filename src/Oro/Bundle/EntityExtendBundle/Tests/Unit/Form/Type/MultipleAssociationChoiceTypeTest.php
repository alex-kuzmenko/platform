<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\MultipleAssociationChoiceType;

class MultipleAssociationChoiceTypeTest extends AssociationTypeTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var MultipleAssociationChoiceType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $config1 = new Config(new EntityConfigId('grouping', 'Test\Entity1'));
        $config2 = new Config(new EntityConfigId('grouping', 'Test\Entity2'));
        $config2->set('groups', []);
        $config3 = new Config(new EntityConfigId('grouping', 'Test\Entity3'));
        $config3->set('groups', ['test']);
        $config4 = new Config(new EntityConfigId('grouping', 'Test\Entity4'));
        $config4->set('groups', ['test', 'test1']);
        $this->groupingConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->groupingConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->will($this->returnValue([$config1, $config2, $config3, $config4]));

        $entityConfig3 = new Config(new EntityConfigId('entity', 'Test\Entity3'));
        $entityConfig3->set('plural_label', 'Entity3');
        $entityConfig4 = new Config(new EntityConfigId('entity', 'Test\Entity4'));
        $entityConfig4->set('plural_label', 'Entity4');
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        ['Test\Entity3', null, $entityConfig3],
                        ['Test\Entity4', null, $entityConfig4],
                    ]
                )
            );

        $entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->will($this->returnArgument(0));

        $this->type = new MultipleAssociationChoiceType(
            new AssociationTypeHelper($this->configManager, $entityClassResolver),
            $this->configManager
        );
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit($newVal, $oldVal, $state, $isSetStateExpected)
    {
        $this->doTestSubmit(
            'items',
            $this->type,
            [
                'config_id'         => new EntityConfigId('test', 'Test\Entity'),
                'association_class' => 'test'
            ],
            [
                'grouping' => $this->groupingConfigProvider,
                'entity'   => $this->entityConfigProvider,
            ],
            $newVal,
            $oldVal,
            $state,
            $isSetStateExpected
        );
    }

    public function submitProvider()
    {
        return [
            [[], null, ExtendScope::STATE_ACTIVE, false],
            [[], [], ExtendScope::STATE_ACTIVE, false],
            [['Test\Entity3'], ['Test\Entity3'], ExtendScope::STATE_ACTIVE, false],
            [[], ['Test\Entity3'], ExtendScope::STATE_ACTIVE, false],
            [['Test\Entity3'], [], ExtendScope::STATE_ACTIVE, true],
            [['Test\Entity3', 'Test\Entity4'], ['Test\Entity4'], ExtendScope::STATE_ACTIVE, true],
            [['Test\Entity3'], ['Test\Entity4'], ExtendScope::STATE_ACTIVE, true],
            [['Test\Entity3'], [], ExtendScope::STATE_UPDATED, false],
        ];
    }

    public function testFinishViewNoConfig()
    {
        $this->prepareFinishViewTest();

        $this->testConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->will($this->returnValue(false));
        $this->testConfigProvider->expects($this->never())
            ->method('getConfig');

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity'),
            'association_class' => 'test'
        ];

        $view->children[0] = new FormView($view);
        $view->children[1] = new FormView($view);

        $view->children[0]->vars['value'] = 'Test\Entity1';
        $view->children[1]->vars['value'] = 'Test\Entity2';

        $this->type->finishView($view, $form, $options);

        $this->assertEquals(
            [
                'attr'  => [],
                'value' => 'Test\Entity1'
            ],
            $view->children[0]->vars
        );
        $this->assertEquals(
            [
                'attr'  => [],
                'value' => 'Test\Entity2'
            ],
            $view->children[1]->vars
        );
    }

    public function testFinishViewNoImmutable()
    {
        $this->prepareFinishViewTest();

        $testConfig = new Config(new EntityConfigId('test', 'Test\Entity'));
        $this->testConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->will($this->returnValue(true));
        $this->testConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity')
            ->will($this->returnValue($testConfig));

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity'),
            'association_class' => 'test'
        ];

        $view->children[0] = new FormView($view);
        $view->children[1] = new FormView($view);

        $view->children[0]->vars['value'] = 'Test\Entity1';
        $view->children[1]->vars['value'] = 'Test\Entity2';

        $this->type->finishView($view, $form, $options);

        $this->assertEquals(
            [
                'attr'  => [],
                'value' => 'Test\Entity1'
            ],
            $view->children[0]->vars
        );
        $this->assertEquals(
            [
                'attr'  => [],
                'value' => 'Test\Entity2'
            ],
            $view->children[1]->vars
        );
    }

    public function testFinishViewWithImmutable()
    {
        $this->prepareFinishViewTest();

        $testConfig = new Config(new EntityConfigId('test', 'Test\Entity'));
        $testConfig->set('immutable', ['Test\Entity1']);
        $this->testConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->will($this->returnValue(true));
        $this->testConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity')
            ->will($this->returnValue($testConfig));

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity'),
            'association_class' => 'test'
        ];

        $view->children[0] = new FormView($view);
        $view->children[1] = new FormView($view);

        $view->children[0]->vars['value'] = 'Test\Entity1';
        $view->children[1]->vars['value'] = 'Test\Entity2';

        $this->type->finishView($view, $form, $options);

        $this->assertEquals(
            [
                'attr'     => [],
                'disabled' => true,
                'value'    => 'Test\Entity1'
            ],
            $view->children[0]->vars
        );
        $this->assertEquals(
            [
                'attr'  => [],
                'value' => 'Test\Entity2'
            ],
            $view->children[1]->vars
        );
    }

    public function testFinishViewForDisabled()
    {
        $this->prepareFinishViewTest();

        $this->testConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->will($this->returnValue(false));
        $this->testConfigProvider->expects($this->never())
            ->method('getConfig');

        $view    = new FormView();
        $form    = new Form($this->getMock('Symfony\Component\Form\FormConfigInterface'));
        $options = [
            'config_id'         => new EntityConfigId('test', 'Test\Entity'),
            'association_class' => 'test'
        ];

        $view->vars['disabled'] = true;

        $view->children[0] = new FormView($view);
        $view->children[1] = new FormView($view);

        $view->children[0]->vars['value'] = 'Test\Entity1';
        $view->children[1]->vars['value'] = 'Test\Entity2';

        $this->type->finishView($view, $form, $options);

        $this->assertEquals(
            [
                'attr'     => [],
                'disabled' => true,
                'value'    => 'Test\Entity1'
            ],
            $view->children[0]->vars
        );
        $this->assertEquals(
            [
                'attr'  => [],
                'disabled' => true,
                'value' => 'Test\Entity2'
            ],
            $view->children[1]->vars
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_entity_extend_multiple_association_choice', $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->type->getParent());
    }

    /**
     * @param string|null $cssClass
     * @return array
     */
    protected function getDisabledFormView($cssClass = null)
    {
        $result = [
            'disabled' => true,
            'attr'     => [],
            'value'    => null
        ];
        if (!empty($cssClass)) {
            $result['attr']['class'] = $cssClass;
        }

        return $result;
    }

    protected function prepareFinishViewTest()
    {
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['test', $this->testConfigProvider],
                    ]
                )
            );
    }
}
