<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\MatchApplicableChecker;
use Oro\Bundle\ApiBundle\Request\RequestType;

class RequestTypeApplicableChecker extends MatchApplicableChecker
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes)
    {
        $result = self::ABSTAIN;

        $attrName = 'requestType';
        if (!empty($processorAttributes[$attrName]) && $context->has($attrName)) {
            /** @var RequestType $requestType */
            $requestType = $context->get($attrName);
            if (!$requestType->isEmpty() && !$this->isMatch($processorAttributes[$attrName], $requestType)) {
                $result = self::NOT_APPLICABLE;
            }
        }

        return $result;
    }
}
