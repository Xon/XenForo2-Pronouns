<?php

namespace SV\Pronouns\Util;

use Collator;
use Spoofchecker;
use XF\CustomField\Definition;
use function array_unique;
use function class_exists;
use function count;
use function forward_static_call_array;
use function implode;
use function is_string;
use function mb_strlen;
use function mb_strtoupper;
use function preg_match;
use function preg_replace;
use function strcasecmp;
use function strlen;
use function trim;

class CustomField
{
    protected static function isSimilarString(string $s1, string $s2)
    {
        if (class_exists(Spoofchecker::class))
        {
            $spoofChecker = new Spoofchecker();
            if ($spoofChecker->areConfusable($s1, $s2))
            {
                return true;
            }
        }

        if (class_exists(Collator::class))
        {
            $c = new Collator(\XF::language()->getLanguageCode());
            $c->setStrength(Collator::PRIMARY);

            return $c->compare($s1, $s2) == 0;
        }

        return strcasecmp($s1, $s2) == 0;
    }

    protected static function ucfirst(string $s): string
    {
        switch (mb_strlen($s))
        {
            case 0:
                return '';
            case 1:
                return mb_strtoupper($s);
            default:
                $firstChar = mb_strtoupper(mb_substr($s, 0, 1));
                $rest = mb_substr($s, 1);

                return $firstChar . $rest;
        }
    }

    /**
     * @param Definition  $definition
     * @param string      $value
     * @param string|null $error
     * @param int         $min
     * @param int         $limit
     * @param bool        $includeWhiteSpace
     * @param bool        $englishOnly
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public static function listValidator(Definition $definition, string &$value, ?string &$error, int $min = 0, int $limit = 0, bool $includeWhiteSpace = true, bool $englishOnly = false): bool
    {
        $value = \normalizer_normalize($value);
        if ($value === false)
        {
            return false;
        }

        // normalize whitespace & trim
        $value = preg_replace('/\s+/u', ' ', $value);
        if (!is_string($value))
        {
            return false;
        }
        $value = trim($value);

        if ($englishOnly)
        {
            $value = preg_replace('/[^a-z\s|\\\\\/,\-_&]/ui', '', $value);
            if (!is_string($value))
            {
                return false;
            }
        }

        if (strlen($value) === 0)
        {
            // allow empty
            return true;
        }

        $user = \XF::visitor();
        if (static::isSimilarString($user->username, $value))
        {
            $value = '';

            return true;
        }

//        if ($englishOnly && !\preg_match('/^[a-z\s\\\\\/,\-_&]*$/i', $value))
//        {
//            $error = \XF::phrase('svUserEssentials_english_characters_only');
//
//            return false;
//        }

        $list = \preg_split('/\s+or\s+|[|\\\\\/,\-_&' . ($includeWhiteSpace ? '\s' : '') . ']/usi', $value, -1, PREG_SPLIT_NO_EMPTY);
        if ($list === false)
        {
            // wat
            return false;
        }

        $username = static::ucfirst($user->username);
        $filterList = [];
        foreach ($list as $item)
        {
            $item = trim($item);
            $item = static::ucfirst($item);
            if (static::isSimilarString($username, $item))
            {
                continue;
            }
            $filterList[] = $item;
        }
        $list = array_unique($filterList);

        if (count($list) == 0)
        {
            $value = '';

            return true;
            //$error = \XF::phrase('svUserEssentials_custom_field_list_not_enough_items', ['limit' => $limit]);

            //return false;
        }
        else if ($limit > 0 && count($list) > $limit)
        {
            $error = \XF::phrase('svUserEssentials_custom_field_list_too_many_items', ['limit' => $limit]);

            return false;
        }

        $value = implode('/', $list);

        return true;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        $class = static::class;
        if (preg_match("#listValidator(en)?(\d+)#i", $name, $matches))
        {
            $englishOnly = false;
            $en = $matches[1];
            if ($en)
            {
                $englishOnly = true;
            }
            $limit = (int)$matches[2];
            if ($limit)
            {
                $arguments[] = 0;
                $arguments[] = $limit;
                $arguments[] = true;
                $arguments[] = $englishOnly;

                return forward_static_call_array([$class, 'listValidator'], $arguments);
            }
        }

        throw new \BadMethodCallException("Static method {$class}::{$name}() doesn't exist");
    }
}