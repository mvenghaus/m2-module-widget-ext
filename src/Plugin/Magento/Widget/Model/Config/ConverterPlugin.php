<?php

declare(strict_types=1);

namespace Inkl\WidgetExtXsd\Plugin\Magento\Widget\Model\Config;

use Magento\Widget\Model\Config\Converter;

class ConverterPlugin
{
    private array $widgets = [];

    public function beforeConvert(Converter $subject, \DOMDocument $source): array
    {
        $xpath = new \DOMXPath($source);
        /** @var $widget \DOMNode */
        foreach ($xpath->query('/widgets/widget') as $widget) {
            foreach ($widget->childNodes as $widgetSubNode) {
                if ($widgetSubNode->nodeName === 'extra') {
                    $widgetId = $widget->attributes->getNamedItem('id');
                    $this->widgets[$widgetId->nodeValue] = [
                        'extra' => $this->convertData($widgetSubNode)
                    ];

                    $widgetSubNode->parentNode->removeChild($widgetSubNode);
                }
            }
        }

        return [$source];
    }

    public function afterConvert(Converter $subject, array $widgets, \DOMDocument $source): array
    {
        foreach ($this->widgets as $widgetId => $widgetData) {
            if (isset($widgets[$widgetId])) {
                $widgets[$widgetId] += $widgetData;
            }
        }

        return $widgets;
    }

    private function convertData(\DOMElement $source)
    {
        $data = [];
        if (!$source->hasChildNodes()) {
            return $data;
        }
        foreach ($source->childNodes as $dataChild) {
            if ($dataChild instanceof \DOMElement) {
                $data[$dataChild->attributes->getNamedItem('name')->nodeValue] = $this->convertData($dataChild);
            } else {
                if ($dataChild->nodeValue && strlen(trim($dataChild->nodeValue))) {
                    $data = $dataChild->nodeValue;
                }
            }
        }
        return $data;
    }
}