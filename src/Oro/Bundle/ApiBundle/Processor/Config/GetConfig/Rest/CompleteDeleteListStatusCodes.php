<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig\Rest;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;

/**
 * Adds possible status codes for the "delete_list" action executed in scope of REST API.
 */
class CompleteDeleteListStatusCodes extends CompleteStatusCodes
{
    /**
     * {@inheritdoc}
     */
    protected function addStatusCodes(StatusCodesConfig $statusCodes)
    {
        if (!$statusCodes->hasCode(Response::HTTP_NO_CONTENT)) {
            $statusCodes->addCode(
                Response::HTTP_NO_CONTENT,
                $this->createStatusCode('Returned when the entities successfully deleted')
            );
        }
        if (!$statusCodes->hasCode(Response::HTTP_FORBIDDEN)) {
            $statusCodes->addCode(
                Response::HTTP_FORBIDDEN,
                $this->createStatusCode('Returned when no permissions to delete the entities')
            );
        }

        parent::addStatusCodes($statusCodes);
    }
}
