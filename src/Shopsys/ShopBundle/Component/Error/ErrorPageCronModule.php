<?php

namespace Shopsys\ShopBundle\Component\Error;

use Shopsys\ShopBundle\Component\Cron\SimpleCronModuleInterface;
use Shopsys\ShopBundle\Component\Error\ErrorPagesFacade;
use Symfony\Bridge\Monolog\Logger;

class ErrorPageCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \Shopsys\ShopBundle\Component\Error\ErrorPagesFacade
     */
    private $errorPagesFacade;

    public function __construct(ErrorPagesFacade $errorPagesFacade)
    {
        $this->errorPagesFacade = $errorPagesFacade;
    }

    /**
     * @inheritdoc
     */
    public function setLogger(Logger $logger)
    {
    }

    public function run()
    {
        $this->errorPagesFacade->generateAllErrorPagesForProduction();
    }
}
