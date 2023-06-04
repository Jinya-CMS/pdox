<?php

namespace Jinya\PDOx;

enum HydratorType
{
    case ArraySerializableHydrator;
    case ClassMethodsHydrator;
    case ObjectPropertyHydrator;
    case ReflectionHydrator;
}
