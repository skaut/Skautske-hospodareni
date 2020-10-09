<?php

declare(strict_types=1);

namespace Model\Infrastructure\Services;

use Psr\Log\LoggerInterface;
use Skautis\SkautisQuery;
use Skautis\Wsdl\WebService;
use Skautis\Wsdl\WsdlManager;
use function sprintf;

final class SkautisCallListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function register(WsdlManager $wsdlManager) : void
    {
        $wsdlManager->addWebServiceListener((string) WebService::EVENT_SUCCESS, [$this, 'handleSuccess']);
        $wsdlManager->addWebServiceListener((string) WebService::EVENT_FAILURE, [$this, 'handleError']);
    }

    public function handleSuccess(SkautisQuery $query) : void
    {
        $this->logger->debug(
            sprintf('Skautis query "%s" sucessfuly performed.', $query->fname),
            $this->prepareContext($query)
        );
    }

    public function handleError(SkautisQuery $query) : void
    {
        $this->logger->debug(
            sprintf('Skautis query "%s" returns error: %s', $query->fname, $query->getExceptionString()),
            $this->prepareContext($query)
        );
    }

    /**
     * @return mixed[]
     */
    private function prepareContext(SkautisQuery $query) : array
    {
        return [
            'arguments' => $query->args,
            'time' => $query->time,
            'result' => $query->result,
        ];
    }
}
