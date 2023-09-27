<?php

namespace Jinya\PDOx;

use Iterator;
use Jinya\PDOx\Exceptions\InvalidQueryException;
use Jinya\PDOx\Exceptions\NoResultException;
use Laminas\Hydrator\AbstractHydrator;
use Laminas\Hydrator\ArraySerializableHydrator;
use Laminas\Hydrator\ClassMethodsHydrator;
use Laminas\Hydrator\NamingStrategy\UnderscoreNamingStrategy;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Hydrator\ReflectionHydrator;
use Laminas\Hydrator\Strategy\StrategyInterface;
use PDO;

use function array_key_exists;
use function count;

class PDOx extends PDO
{
    private bool $useHydrator;
    private HydratorType $hydratorType = HydratorType::ReflectionHydrator;
    private string $noResultBehavior = self::PDOX_NO_RESULT_BEHAVIOR_NULL;

    public const PDOX_NAMING_UNDERSCORE_TO_CAMELCASE = 'NAMING_UNDERSCORE_TO_CAMELCASE';
    public const PDOX_NO_RESULT_BEHAVIOR = 'PDOX_NO_RESULT_BEHAVIOR';
    public const PDOX_NO_RESULT_BEHAVIOR_NULL = 'PDOX_NO_RESULT_BEHAVIOR_NULL';
    public const PDOX_NO_RESULT_BEHAVIOR_EXCEPTION = 'PDOX_NO_RESULT_BEHAVIOR_EXCEPTION';
    public const PDOX_HYDRATOR_TYPE = 'PDOX_HYDRATOR_TYPE';

    /**
     * Generates a new ReflectionHydrator
     *
     * @return AbstractHydrator
     */
    private function getHydrator(): AbstractHydrator
    {
        $hydrator = match ($this->hydratorType) {
            HydratorType::ArraySerializableHydrator => new ArraySerializableHydrator(),
            HydratorType::ClassMethodsHydrator => new ClassMethodsHydrator(),
            HydratorType::ObjectPropertyHydrator => new ObjectPropertyHydrator(),
            HydratorType::ReflectionHydrator => new ReflectionHydrator(),
        };
        $hydrator->setNamingStrategy(new UnderscoreNamingStrategy());

        return $hydrator;
    }

    /**
     * PDOx constructor.
     * @param string $dsn
     * @param string|null $username
     * @param string|null $password
     * @param array<string|int, string|int|bool|HydratorType>|null $options
     */
    public function __construct(string $dsn, string $username = null, string $password = null, array $options = null)
    {
        parent::__construct($dsn, $username, $password, $options);
        if ($options && array_key_exists(self::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE, $options) && $options[self::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE] === false) {
            $this->useHydrator = false;
        } else {
            $this->useHydrator = true;
        }

        if ($options && array_key_exists(self::PDOX_NO_RESULT_BEHAVIOR, $options) && $options[self::PDOX_NO_RESULT_BEHAVIOR] === self::PDOX_NO_RESULT_BEHAVIOR_EXCEPTION) {
            $this->noResultBehavior = self::PDOX_NO_RESULT_BEHAVIOR_EXCEPTION;
        }
        if ($options && array_key_exists(self::PDOX_HYDRATOR_TYPE, $options)) {
            /** @phpstan-ignore-next-line */
            $this->hydratorType = $options[self::PDOX_HYDRATOR_TYPE];
        }
    }

    /**
     * @param object[] $data
     * @return bool
     * @throws InvalidQueryException
     * @throws NoResultException
     */
    private function checkFetchObjectForCount(array $data): bool
    {
        if (count($data) > 1) {
            throw new InvalidQueryException('Query returned more than one result');
        }

        if (count($data) === 0) {
            if ($this->noResultBehavior === self::PDOX_NO_RESULT_BEHAVIOR_EXCEPTION) {
                throw new NoResultException('Query returned no result');
            }

            return false;
        }

        return true;
    }

    /**
     * @param string $query
     * @param object $prototype
     * @param array<int|string, mixed>|null $parameters
     * @param StrategyInterface[] $strategies
     * @return mixed|null
     * @throws InvalidQueryException
     * @throws NoResultException
     */
    public function fetchObject(string $query, object $prototype, array $parameters = null, array $strategies = []): mixed
    {
        $stmt = $this->prepare($query);
        $result = $stmt->execute($parameters);
        if ($result) {
            if ($this->useHydrator) {
                $data = $stmt->fetchAll(self::FETCH_ASSOC);
                if (!is_array($data) || !$this->checkFetchObjectForCount($data)) {
                    return null;
                }

                $hydrator = $this->getHydrator();
                foreach ($strategies as $key => $strategy) {
                    $hydrator->addStrategy($key, $strategy);
                }

                $prototypeClass = get_class($prototype);

                return $hydrator->hydrate($data[0], new $prototypeClass());
            }

            $stmt->setFetchMode(self::FETCH_CLASS, get_class($prototype));
            $data = $stmt->fetchAll(self::FETCH_CLASS);
            if (!is_array($data) || !$this->checkFetchObjectForCount($data)) {
                return null;
            }

            return $data[0];
        }

        throw new InvalidQueryException('Failed to execute query', errorInfo: $stmt->errorInfo());
    }

    /**
     * @param string $query
     * @param object $prototype
     * @param array<int|string, mixed>|null $parameters
     * @param StrategyInterface[] $strategies
     * @return Iterator<mixed>
     * @throws InvalidQueryException
     */
    public function fetchIterator(string $query, object $prototype, array $parameters = null, array $strategies = []): Iterator
    {
        $stmt = $this->prepare($query);
        $result = $stmt->execute($parameters);
        if ($result) {
            if ($this->useHydrator) {
                $hydrator = $this->getHydrator();
                $data = $stmt->fetchAll(self::FETCH_ASSOC);
                if ($data !== false) {
                    foreach ($strategies as $key => $strategy) {
                        $hydrator->addStrategy($key, $strategy);
                    }
                    $prototypeClass = get_class($prototype);

                    foreach ($data as $item) {
                        yield $hydrator->hydrate($item, new $prototypeClass());
                    }
                }
            } else {
                $stmt->setFetchMode(self::FETCH_CLASS, get_class($prototype));
                $data = $stmt->fetchAll();

                if ($data !== false) {
                    foreach ($data as $item) {
                        yield $item;
                    }
                }
            }
        } else {
            throw new InvalidQueryException('Failed to execute query', errorInfo: $stmt->errorInfo());
        }
    }

    /**
     * @param string $query
     * @param object $prototype
     * @param array<int|string, mixed>|null $parameters
     * @param StrategyInterface[] $strategies
     * @return array<object>
     * @throws InvalidQueryException
     */
    public function fetchArray(string $query, object $prototype, array $parameters = null, array $strategies = []): array
    {
        $stmt = $this->prepare($query);
        $result = $stmt->execute($parameters);
        $items = [];
        if ($result) {
            if ($this->useHydrator) {
                $data = $stmt->fetchAll(self::FETCH_ASSOC);
                $hydrator = $this->getHydrator();
                if ($data !== false) {
                    foreach ($strategies as $key => $strategy) {
                        $hydrator->addStrategy($key, $strategy);
                    }
                    $prototypeClass = get_class($prototype);

                    foreach ($data as $item) {
                        $items[] = $hydrator->hydrate($item, new $prototypeClass());
                    }

                    return $items;
                }
            } else {
                $stmt->setFetchMode(self::FETCH_CLASS, get_class($prototype));

                $data = $stmt->fetchAll();
                if ($data) {
                    return $data;
                }

                return [];
            }
        }

        throw new InvalidQueryException('Failed to execute query', errorInfo: $stmt->errorInfo());
    }
}
