<?php

declare(strict_types=1);

namespace Shopware\Core\Framework\Validation;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\Constraint\Uuid;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\MetadataInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Package('core
calling into the validator machinery has a considerable overhead. Doing that thousands of time is notable.
this validator implements a subset of the functionality and calls into the real validator if needed.')]
class HappyPathValidator implements ValidatorInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly ValidatorInterface $inner)
    {
    }

    /**
     * @param Constraint|Constraint[]|null $constraints
     */
    public function validate(mixed $value, $constraints = null, $groups = null): ConstraintViolationListInterface
    {
        if ($constraints === null) {
            return $this->inner->validate($value, $constraints, $groups);
        }

        $constraints = \is_array($constraints) ? $constraints : [$constraints];

        foreach ($constraints as $constraint) {
            // if one of our checks fails, we call the real validator
            if (!$this->validateConstraint($value, $constraint)) {
                return $this->inner->validate($value, $constraints, $groups);
            }
        }

        return new ConstraintViolationList();
    }

    public function getMetadataFor(mixed $value): MetadataInterface
    {
        return $this->inner->getMetadataFor($value);
    }

    public function hasMetadataFor(mixed $value): bool
    {
        return $this->inner->hasMetadataFor($value);
    }

    /**
     * @param object $object can not use native type hint as it is incompatible with symfony <5.3.4
     */
    public function validateProperty($object, $propertyName, $groups = null): ConstraintViolationListInterface
    {
        return $this->inner->validateProperty($object, $propertyName, $groups);
    }

    public function validatePropertyValue($objectOrClass, $propertyName, $value, $groups = null): ConstraintViolationListInterface
    {
        return $this->inner->validatePropertyValue($objectOrClass, $propertyName, $value, $groups);
    }

    public function startContext(): ContextualValidatorInterface
    {
        return $this->inner->startContext();
    }

    public function inContext(ExecutionContextInterface $context): ContextualValidatorInterface
    {
        return $this->inner->inContext($context);
    }

    /**
     * @param string|int|float|bool|array|object|callable|resource|null $value
     * @param Constraint|Constraint[]|null                              $constraint
     *
     * @return string|int|float|bool|array|object|callable|resource|null
     */
    private function normalizeValueIfRequired($value, $constraint)
    {
        if (!$constraint instanceof Constraint) {
            return $value;
        }

        if (!\property_exists($constraint, 'normalizer')) {
            return $value;
        }

        if (empty($constraint->normalizer)) {
            return $value;
        }

        $normalizer = $constraint->normalizer;

        if (!\is_callable($normalizer)) {
            return $value;
        }

        return $normalizer($value);
    }

    /**
     * @param string|int|float|bool|array|object|callable|resource|null $value
     * @param Constraint|Constraint[]|null                              $constraint
     */
    private function validateConstraint($value, $constraint): bool
    {
        // apply defined normalizers to check $value from constraint if defined
        $value = $this->normalizeValueIfRequired(
            $value,
            $constraint
        );

        switch (true) {
            case $constraint instanceof Uuid:
                if ($value !== null && \is_string($value) && !\Shopware\Core\Framework\Uuid\Uuid::isValid($value)) {
                    return false;
                }

                break;
            case $constraint instanceof NotBlank:
                if ($value === false || (empty($value) && $value !== '0')) {
                    return false;
                }

                break;
            case $constraint instanceof NotNull:
                if ($value === null) {
                    return false;
                }

                break;
            case $constraint instanceof Type:
                $types = (array) $constraint->type;

                foreach ($types as $type) {
                    $type = strtolower((string) $type);
                    $type = $type === 'boolean' ? 'bool' : $type;
                    $isFunction = 'is_' . $type;
                    $ctypeFunction = 'ctype_' . $type;

                    if (\function_exists($isFunction)) {
                        if (\is_callable($isFunction) && !$isFunction($value)) {
                            return false;
                        }
                    } elseif (\function_exists($ctypeFunction)) {
                        if (\is_callable($ctypeFunction) && !$ctypeFunction($value)) {
                            return false;
                        }
                    } elseif (!$value instanceof $type) {
                        return false;
                    }
                }

                break;
            case $constraint instanceof Length:
                if (!\is_string($value)) {
                    return false;
                }
                $length = mb_strlen($value);

                if ($constraint->max !== null && $length > $constraint->max) {
                    return false;
                }

                if ($constraint->min !== null && $length < $constraint->min) {
                    return false;
                }

                break;
            case $constraint instanceof Range:
                if (!is_numeric($value)) {
                    return false;
                }

                if ($constraint->min === null && $constraint->max !== null) {
                    if ($value > $constraint->max) {
                        return false;
                    }
                } elseif ($constraint->min !== null && $constraint->max === null) {
                    if ($value < $constraint->min) {
                        return false;
                    }
                } elseif ($constraint->min !== null && $constraint->max !== null) {
                    if ($value < $constraint->min || $value > $constraint->max) {
                        return false;
                    }
                }

                break;

            case $constraint instanceof Collection:
                foreach ($constraint->fields as $field => $fieldConstraint) {
                    // bug fix issue #2779
                    $existsInArray = \is_array($value) && \array_key_exists($field, $value);
                    $existsInArrayAccess = $value instanceof \ArrayAccess && $value->offsetExists($field);

                    if ($existsInArray || $existsInArrayAccess) {
                        if ((is_countable($fieldConstraint->constraints) ? \count($fieldConstraint->constraints) : 0) > 0) {
                            /** @var array|\ArrayAccess<string|int,mixed> $value */
                            if (!$this->validateConstraint($value[$field], $fieldConstraint->constraints)) {
                                return false;
                            }
                        }
                    } elseif (!$fieldConstraint instanceof Optional && !$constraint->allowMissingFields) {
                        return false;
                    }

                    if (!$constraint->allowExtraFields && is_iterable($value)) {
                        foreach ($value as $f => $_) {
                            if (!isset($constraint->fields[$f])) {
                                return false;
                            }
                        }
                    }
                }

                break;
            // unknown constraint
            default:
                return false;
        }

        return true;
    }
}
