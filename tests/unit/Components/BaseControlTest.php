<?php

declare(strict_types=1);

namespace App\Components;

use App\BasePresenter;
use Codeception\Test\Unit;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\UrlScript;
use Nette\InvalidStateException;

/**
 * Hlídá kontrakt Nette pro getPresenter(): Nette 3.3 se před vytvořením podkomponenty ptá
 * přes getPresenter(throw: false) a u nepřipojené komponenty očekává null, ne výjimku.
 */
final class BaseControlTest extends Unit
{
    public function testDetachedControlReturnsNullWhenPresenterIsOptional(): void
    {
        self::assertNull($this->createControl()->getPresenter(false));
    }

    public function testDetachedControlThrowsWhenPresenterIsRequired(): void
    {
        $this->expectException(InvalidStateException::class);

        $this->createControl()->getPresenter();
    }

    public function testAttachedControlReturnsItsPresenter(): void
    {
        $presenter = new class extends BasePresenter {
        };
        $presenter->injectPrimary(new Request(new UrlScript('http://localhost/')), new Response());

        $control = $this->createControl();
        $presenter->addComponent($control, 'control');

        self::assertSame($presenter, $control->getPresenter());
        self::assertSame($presenter, $control->getPresenter(false));
    }

    private function createControl(): BaseControl
    {
        return new class extends BaseControl {
            public function render(): void
            {
            }
        };
    }
}
