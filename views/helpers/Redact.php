<?php
/**
 * Redact Elements
 * 
 * @copyright Copyright 2007-2014 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * Redact text view helper
 * 
 * @package Omeka\Plugins\RedactElements
 */
class RedactElements_View_Helper_Redact extends Zend_View_Helper_Abstract
{
    /**
     * Redact a text
     *
     * @param string $text Redact matching strings contained in this text
     * @param array $patterns Regular expression patterns to match against
     * @param string $replacement Replace matching strings with this string
     * @return string
     */
    public function redact($text, array $patterns, $replacement)
    {
        foreach ($patterns as $pattern) {
            $text = preg_replace("/$pattern/", $replacement, $text);
        }
        return $text;
    }
}
