<?php
/**
 * Redact text view helper
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
