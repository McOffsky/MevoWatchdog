<?php

namespace App\Request;

class OSRMPathRequest
{
    private $from = [];
    private $to = [];
    private $steps = true;

    /**
     * OSRMPathRequest constructor.
     * @param array|string $from
     * @param array|string $to
     * @param $steps
     */
    public function __construct($from, $to, $steps = true)
    {
        if (!is_array($from)) {
            $from = $this->getLocationArray($from);
        }

        if (!is_array($to)) {
            $to = $this->getLocationArray($to);
        }

        $this->from = $from;
        $this->to = $to;
        $this->steps = $steps;
    }

    /**
     * @return string
     */
    public function getRequestParameters()
    {
        $steps = $this->steps ? "steps=true" : "";

        return implode(',', $this->from) . ";" . implode(',', $this->to) . "?" . $steps;
    }

    /**
     * @return string
     */
    public function getPathIdentifier()
    {
        $fromSum = $this->from[0] + $this->from[1];
        $toSum = $this->to[0] + $this->to[1];

        if ($fromSum == $toSum) {
            if ($this->from[1] > $this->to[1]) {
                return $this->generateIdentifier($this->from, $this->to, $this->steps);
            } else {
                return $this->generateIdentifier($this->to, $this->from, $this->steps);
            }
        }

        if ($fromSum > $toSum) {
            return $this->generateIdentifier($this->from, $this->to, $this->steps);
        }

        return $this->generateIdentifier($this->to, $this->from, $this->steps);
    }

    /**
     * @param string $from
     * @param string $to
     * @param boolean $steps
     * @return string
     */
    public function generateIdentifier($from, $to, $steps)
    {
        $steps = $steps ? "steps=true" : "";

        return implode(',', $from) . ";" . implode(',', $to) . ";" . $steps;
    }

    /**
     * @param string $string
     * @return array
     */
    private function getLocationArray($string): ?array
    {
        $data = explode("|", $string);

        return [floatval($data[1]), floatval($data[0])];
    }
}