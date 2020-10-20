<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Template\Engine\Repository;

use LDL\Template\Contracts\TemplateEngineInterface;
use LDL\Type\Collection\Interfaces;
use LDL\Type\Collection\Traits\Selection\SingleSelectionTrait;
use LDL\Type\Collection\Types\Object\ObjectCollection;
use LDL\Type\Collection\Types\Object\Validator\InterfaceComplianceItemValidator;
use LDL\Type\Collection\Interfaces\Selection\SingleSelectionInterface;

class TemplateEngineRepository extends ObjectCollection implements SingleSelectionInterface
{

    use SingleSelectionTrait;

    /**
     * @var string
     */
    private $selected;

    /**
     * @var string
     */
    private $last;

    public function __construct(iterable $items = null)
    {
        parent::__construct($items);

        $this->getValidatorChain()
            ->append(
                new InterfaceComplianceItemValidator(TemplateEngineInterface::class)
            );
    }

    public function append($item, $key = null): Interfaces\CollectionInterface
    {
        if(null === $key){
            $msg = sprintf(
              '"%s", requires a key which identifies the template engine',
                __CLASS__
            );

            throw new \RuntimeException($msg);
        }

        $this->last = $key;

        return parent::append($item, $key);
    }

}

