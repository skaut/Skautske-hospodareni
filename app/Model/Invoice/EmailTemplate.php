<?php

declare(strict_types=1);

namespace App\Model\Invoice;

use App\Model\Invoice\Entity\Invoice;

use function array_keys;
use function array_values;
use function str_replace;

class EmailTemplate
{
    private string $subject;

    private string $body;

    public function __construct(string $subject, string $body)
    {
        $this->subject = $subject;
        $this->body = $body;
    }

    public function evaluate(Invoice $invoice, string $user): self
    {
        $parameters = [
            '%number%' => $invoice->getInvoiceNumber(),
            '%customer_name%' => $invoice->getCustomerDisplayName(),
            '%amount%' => (string) $invoice->getTotalAmount(),
            '%maturity%' => $invoice->getDueDate()->format('j.n.Y'),
            '%maturityus%' => $invoice->getDueDate()->format('Y-m-d'),
            '%vs%' => (string) $invoice->getVariableSymbol(),
            '%user%' => $user,
        ];

        return new self(
            $this->replace($parameters, $this->subject),
            $this->replace($parameters, $this->body),
        );
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /** @param array<string, string> $parameters */
    private function replace(array $parameters, string $template): string
    {
        return str_replace(array_keys($parameters), array_values($parameters), $template);
    }
}
