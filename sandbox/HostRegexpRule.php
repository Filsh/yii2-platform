<?php

namespace yii\platform\sandbox;

class HostRegexpRule extends Rule
{
    public $includePattern;
    
    public $excludePattern;
    
    public function isValid()
    {
        $value = $this->getValue();
        if (!$value || !is_string($value)) {
            throw new NotDetectingException('Invalid value given or host is not detecting.');
        }
        if(!$this->includePattern || !is_string($this->includePattern)) {
            throw new \yii\base\InvalidParamException('Invalid value given, include pattern should be valid regular expression.');
        }
        if($this->excludePattern !== null && !is_string($this->excludePattern)) {
            throw new \yii\base\InvalidParamException('Invalid value given, exclude pattern should be null or a valid regular expression.');
        }
        
        $status = preg_match($this->includePattern, $value);
        if($this->excludePattern !== null) {
            $status = $status && !preg_match($this->excludePattern, $value);
        }
        
        return (bool) $status;
    }
    
    protected function getValue()
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            return false;
        }
        $host = $_SERVER['HTTP_HOST'];
        if (substr($host, 0, 4) == 'www.') {
            $host = substr($host, 4);
        }
        return $host;
    }
}