<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Common\Stubs\DataAbstractionLayer;

use Shopware\Core\Checkout\Test\Cart\Promotion\Helpers\Fakes\FakeConnection;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\FieldAccessorBuilder\DefaultFieldAccessorBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\FieldAccessorBuilder\FieldAccessorBuilderInterface;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\BoolFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\CustomFieldsSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\FieldSerializerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\FkFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\FloatFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\IdFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\IntFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\ManyToManyAssociationFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\ManyToOneAssociationFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\OneToManyAssociationFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\OneToOneAssociationFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\StringFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteCommandExtractor;
use Shopware\Core\Framework\Util\HtmlSanitizer;
use Shopware\Core\System\CustomField\CustomFieldService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
class StaticDefinitionInstanceRegistry extends DefinitionInstanceRegistry
{
    /**
     * @var FieldSerializerInterface[]
     */
    private array $serializers;

    /**
     * @param class-string<EntityDefinition>[] $registeredDefinitions
     */
    public function __construct(array $registeredDefinitions, private readonly ValidatorInterface $validator, private readonly EntityWriteGatewayInterface $entityWriteGateway)
    {
        parent::__construct(new ContainerBuilder(), [], []);

        $this->setUpSerializers();

        foreach ($registeredDefinitions as $definitionClass) {
            $this->register(new $definitionClass());
        }
    }

    public function getSerializer(string $serializerClass): FieldSerializerInterface
    {
        return $this->serializers[$serializerClass];
    }

    public function getAccessorBuilder(string $accessorBuilderClass): FieldAccessorBuilderInterface
    {
        return new DefaultFieldAccessorBuilder();
    }

    private function setUpSerializers(): void
    {
        $this->serializers = [
            IdFieldSerializer::class => new IdFieldSerializer($this->validator, $this),
            FkFieldSerializer::class => new FkFieldSerializer($this->validator, $this),
            StringFieldSerializer::class => new StringFieldSerializer($this->validator, $this, new HtmlSanitizer()),
            IntFieldSerializer::class => new IntFieldSerializer($this->validator, $this),
            FloatFieldSerializer::class => new FloatFieldSerializer($this->validator, $this),
            BoolFieldSerializer::class => new BoolFieldSerializer($this->validator, $this),
            JsonFieldSerializer::class => new JsonFieldSerializer($this->validator, $this),
            CustomFieldsSerializer::class => new CustomFieldsSerializer(
                $this,
                $this->validator,
                new CustomFieldService(new FakeConnection([['foo', 'int']])),
                new WriteCommandExtractor($this->entityWriteGateway, $this)
            ),
            ManyToManyAssociationFieldSerializer::class => new ManyToManyAssociationFieldSerializer(
                new WriteCommandExtractor($this->entityWriteGateway, $this),
            ),
            ManyToOneAssociationFieldSerializer::class => new ManyToOneAssociationFieldSerializer(
                new WriteCommandExtractor($this->entityWriteGateway, $this),
            ),
            OneToManyAssociationFieldSerializer::class => new OneToManyAssociationFieldSerializer(
                new WriteCommandExtractor($this->entityWriteGateway, $this),
            ),
            OneToOneAssociationFieldSerializer::class => new OneToOneAssociationFieldSerializer(
                new WriteCommandExtractor($this->entityWriteGateway, $this),
            ),
        ];
    }
}
