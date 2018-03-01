<?php
namespace Rexlabs\Smokescreen\Includes;

class IncludeParser implements IncludeParserInterface
{
    /**
     * Parse given string into an Includes object
     * @param string $str
     * @return Includes
     */
    public function parse(string $str): Includes
    {
        // Ignore whitepsace
        $str = \preg_replace('/\s/', '', $str);

        if (empty($str)) {
            return new Includes();
        }

        // Parse a string in the following format:
        // pets{id,name,owner{id,name},photos:limit(3)}:limit(5):offset(10)

        // Define the current parse state
        $state = [
            // Original string
            'string' => $str,
            // Position in string
            'pos' => 0,
            // Length of the original string being processed
            'len' => 0,
            // Current character being processed
            'char' => null,
            // The accumulated current key for the field
            'buffer' => '',
            // The current parent keys
            'parent' => [],
            // Previous parent
            'prevParent' => null,
            // Our list of keys
            'keys' => [],
            // Our list of params
            'params' => [],
        ];

        // Process each character, moving through the state and build
        while ($state['pos'] < ($len = \strlen($str))) {
            $state['char'] = $str[$state['pos']];
            $state['len'] = $len;

            switch ($state['char']) {
                case '{':
                    // Begin children
                    if (!empty($state['buffer'])) {
                        $state['keys'][] = $this->prefixParentKeys($state['buffer'], $state['parent']);
                        $state['parent'][] = $state['buffer'];
                        $state['buffer'] = '';
                    }
                    break;
                case ',':
                    // Delimiter
                    if (!empty($state['buffer'])) {
                        $state['keys'][] = $this->prefixParentKeys($state['buffer'], $state['parent']);
                        $state['buffer'] = '';
                    }
                    break;
                case '}':
                    // End children
                    if (!empty($state['buffer'])) {
                        $state['keys'][] = $this->prefixParentKeys($state['buffer'], $state['parent']);
                        $state['buffer'] = '';
                    }
                    if (!empty($state['parent'])) {
                        $state['prevParent'] = $state['parent'];
                        array_pop($state['parent']);
                    }
                    break;
                case ':':
                    // Looks like it's a parameter. Eg. :limit(10)
                    // Well, if we have a buffer, then that's our parent, if we don't
                    // we will use the parent we saved when we popped the last parent state.
                    $parentKey = !empty($state['buffer']) ?
                        $this->prefixParentKeys($state['buffer'], $state['parent']) : $this->flattenKeys($state['prevParent']);

                    if (preg_match('/^:(\w+)\(([^)]+)\)/', substr($str, $state['pos']), $match)) {
                        // We have a match
                        list($param, $key, $val) = $match;
                        $len = \strlen($param);

                        // Initialise the parent key in our params associative array
                        if (!isset($state['params'][$parentKey])) {
                            $state['params'][$parentKey] = [];
                        }

                        // Store the param key and value
                        $state['params'][$parentKey][$key] = $val;

                        // Chop our parameter out of the original string
                        $str =
                            substr($str, 0, $state['pos']) .
                            substr($str, $state['pos'] + $len);

                        // We need to move the position head back one after the chop
                        // since it will be advanced at the end of the loop
                        $state['pos']--;
                    }
                    break;
                default:
                    // Any other character should just be appended
                    $state['buffer'] .= $state['char'];
                    break;
            }
            $state['pos']++;
        }

        // Finally handle any non-empty buffer
        if (!empty($state['buffer'])) {
            $state['keys'][] = $this->prefixParentKeys($state['buffer'], $state['parent']);
            $state['buffer'] = '';
        }

        return (new Includes())
            ->set($state['keys'])
            ->setParams($state['params']);
    }

    /**
     * Helper function to prefix all of the parent keys
     * @param string $key
     * @param array $parent
     * @return string
     */
    protected function prefixParentKeys($key, array $parent): string
    {
        return !empty($parent) ?
            $this->flattenKeys($parent) . ".$key" : $key;
    }

    /**
     * @param array $keys
     * @return string
     */
    protected function flattenKeys(array $keys): string
    {
        return implode('.', $keys);
    }
}