<?php

namespace Oro\Bundle\QueryDesignerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Configuration;

class ConfigurationPass implements CompilerPassInterface
{
    const MANAGER_SERVICE_ID = 'oro_query_designer.query_designer.manager';
    const TAG_NAME           = 'oro_filter.extension.orm_filter.filter';
    const CONFIG_FILE_NAME   = 'query_designer.yml';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::MANAGER_SERVICE_ID)) {
            $managerDef = $container->getDefinition(self::MANAGER_SERVICE_ID);

            $configs = array();
            foreach ($container->getParameter('kernel.bundles') as $bundle) {
                $reflection = new \ReflectionClass($bundle);
                $file       = dirname($reflection->getFilename()) . '/Resources/config/' . self::CONFIG_FILE_NAME;
                if (is_file($file)) {
                    $config = Yaml::parse(realpath($file))[Configuration::ROOT_NODE_NAME];
                    $vendor = strtolower(substr($bundle, 0, strpos($bundle, '\\')));
                    $this->updateLabelsOfFunctions($config, 'converters', $vendor);
                    $this->updateLabelsOfFunctions($config, 'aggregates', $vendor);
                    $configs[] = $config;
                }
            }

            $filterTypes = [];
            $filters     = $container->findTaggedServiceIds(self::TAG_NAME);
            foreach ($filters as $serviceId => $tags) {
                $attr = reset($tags);
                $managerDef->addMethodCall('addFilter', array($attr['type'], new Reference($serviceId)));
                $filterTypes[] = $attr['type'];
            }

            $processor = new Processor();
            $config    = $processor->processConfiguration(new Configuration($filterTypes), $configs);
            $managerDef->replaceArgument(0, $config);
        }
    }

    /**
     * Updates a label for all functions for the specified group type
     *
     * @param array  $config
     * @param string $groupType
     * @param string $vendor
     */
    protected function updateLabelsOfFunctions(&$config, $groupType, $vendor)
    {
        if (isset($config[$groupType])) {
            foreach ($config[$groupType] as $groupName => &$group) {
                if (isset($group['functions'])) {
                    foreach ($group['functions'] as &$func) {
                        $this->updateFunctionLabel($func, 'name', $vendor, $groupType, $groupName);
                        $this->updateFunctionLabel($func, 'hint', $vendor, $groupType, $groupName);
                    }
                }
            }
        }
    }

    /**
     * Updates a label for the given function
     *
     * @param array  $func
     * @param string $labelType The type of label. Can be 'name' or 'hint'
     * @param string $vendor
     * @param string $groupType
     * @param string $groupName
     */
    protected function updateFunctionLabel(&$func, $labelType, $vendor, $groupType, $groupName)
    {
        $labelName = $labelType . '_label';
        if (!isset($func[$labelName])) {
            $func[$labelName] = sprintf(
                '%s.query_designer.%s.%s.%s.%s',
                $vendor,
                $groupType,
                $groupName,
                $func['name'],
                $labelType
            );
        } elseif ($func[$labelName] === true) {
            // this function should use a label of overridden function
            $func[$labelName] = '';
        }
    }
}
