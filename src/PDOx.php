<?php

namespace Jinya\PDOx;

use Jinya\PDOx\Exceptions\InvalidQueryException;
use Jinya\PDOx\Exceptions\NoResultException;
use Laminas\Hydrator\NamingStrategy\UnderscoreNamingStrategy;
use Laminas\Hydrator\ReflectionHydrator;
use PDO;
use function array_key_exists;
use function count;

class PDOx extends PDO
{
    private ReflectionHydrator $hydrator;
    private bool $useReflectionHydrator;
    private string $noResultBehavior = self::PDOX_NO_RESULT_BEHAVIOR_NULL;

    public const PDOX_NAMING_UNDERSCORE_TO_CAMELCASE = 'NAMING_UNDERSCORE_TO_CAMELCASE';
    public const PDOX_NO_RESULT_BEHAVIOR = 'PDOX_NO_RESULT_BEHAVIOR';
    public const PDOX_NO_RESULT_BEHAVIOR_NULL = 'PDOX_NO_RESULT_BEHAVIOR_NULL';
    public const PDOX_NO_RESULT_BEHAVIOR_EXCEPTION = 'PDOX_NO_RESULT_BEHAVIOR_EXCEPTION';

    public function __construct($dsn, $username = null, $password = null, $options = null)
    {
        parent::__construct($dsn, $username, $password, $options);
        $this->hydrator = new ReflectionHydrator();
        if ($options && array_key_exists(self::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE, $options) && $options[self::PDOX_NAMING_UNDERSCORE_TO_CAMELCASE] === false) {
            $this->useReflectionHydrator = false;
            $this->hydrator->removeNamingStrategy();
        } else {
            $this->useReflectionHydrator = true;
            $this->hydrator->setNamingStrategy(new UnderscoreNamingStrategy());
        }

        if ($options && array_key_exists(self::PDOX_NO_RESULT_BEHAVIOR, $options) && $options[self::PDOX_NO_RESULT_BEHAVIOR] === self::PDOX_NO_RESULT_BEHAVIOR_EXCEPTION) {
            $this->noResultBehavior = self::PDOX_NO_RESULT_BEHAVIOR_EXCEPTION;
        }
    }

    /**
     * @throws InvalidQueryException
     * @throws NoResultException
     */
    public function fetchObject(string $query, object $prototype, array $parameters = null, array $strategies = []): mixed
    {
        $stmt = $this->prepare($query);
        $result = $stmt->execute($parameters);
        if ($result) {
            if ($this->useReflectionHydrator) {
                $data = $stmt->fetchAll(self::FETCH_ASSOC);
                if (count($data) > 1) {
                    throw new InvalidQueryException('Query returned more than one result');
                } elseif (count($data) === 0) {
                    if ($this->noResultBehavior === self::PDOX_NO_RESULT_BEHAVIOR_EXCEPTION) {
                        throw new NoResultException('Query returned no result');
                    }

                    return null;
                }

                foreach ($strategies as $key => $strategy) {
                    $this->hydrator->addStrategy($key, $strategy);
                }

                $item = $this->hydrator->hydrate($data[0], $prototype);
                foreach ($strategies as $key => $strategy) {
                    $this->hydrator->removeStrategy($key);
                }

                return $item;
            }

            $stmt->setFetchMode(self::FETCH_CLASS, get_class($prototype));
            $data = $stmt->fetchAll(self::FETCH_CLASS);
            if (count($data) > 1) {
                throw new InvalidQueryException('Query returned more than one result');
            } elseif (count($data) === 0) {
                if ($this->noResultBehavior === self::PDOX_NO_RESULT_BEHAVIOR_EXCEPTION) {
                    throw new NoResultException('Query returned no result');
                }

                return null;
            }

            return $data[0];
        }

        throw new InvalidQueryException('Failed to execute query', errorInfo: $stmt->errorInfo());
    }
}