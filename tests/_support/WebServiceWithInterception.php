<?php

declare(strict_types=1);

namespace Hskauting\Tests;

use Skautis\InvalidArgumentException;
use Skautis\Wsdl\WebService;
use VCR\Util\SoapClient;

class WebServiceWithInterception extends WebService
{
    public function __construct($wsdl, array $soapOpts)
    {
        $this->init = $soapOpts;

        if (empty($wsdl)) {
            throw new InvalidArgumentException("WSDL address cannot be empty.");
        }

        $this->soapClient = new SoapClient($wsdl, $soapOpts);
    }
}
