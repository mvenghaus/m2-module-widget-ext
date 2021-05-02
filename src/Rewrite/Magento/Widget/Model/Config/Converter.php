<?php

declare(strict_types=1);

namespace Inkl\WidgetExtXsd\Rewrite\Magento\Widget\Model\Config;

class Converter extends \Magento\Widget\Model\Config\Converter
{
    public function convert($source)
    {
        $widgets = [];
        $xpath = new \DOMXPath($source);
        /** @var $widget \DOMNode */
        foreach ($xpath->query('/widgets/widget') as $widget) {
            $widgetAttributes = $widget->attributes;
            $widgetArray = ['@' => []];
            $widgetArray['@']['type'] = $widgetAttributes->getNamedItem('class')->nodeValue;

            $isEmailCompatible = $widgetAttributes->getNamedItem('is_email_compatible');
            if ($isEmailCompatible !== null) {
                $widgetArray['is_email_compatible'] = $isEmailCompatible->nodeValue == 'true' ? '1' : '0';
            }
            $placeholderImage = $widgetAttributes->getNamedItem('placeholder_image');
            if ($placeholderImage !== null) {
                $widgetArray['placeholder_image'] = $placeholderImage->nodeValue;
            }

            $widgetId = $widgetAttributes->getNamedItem('id');
            /** @var $widgetSubNode \DOMNode */
            foreach ($widget->childNodes as $widgetSubNode) {
                switch ($widgetSubNode->nodeName) {
                    case 'label':
                        $widgetArray['name'] = $widgetSubNode->nodeValue;
                        break;
                    case 'description':
                        $widgetArray['description'] = $widgetSubNode->nodeValue;
                        break;
                    case 'parameters':
                        /** @var $parameter \DOMNode */
                        foreach ($widgetSubNode->childNodes as $parameter) {
                            if ($parameter->nodeName === '#text' || $parameter->nodeName === '#comment') {
                                continue;
                            }
                            $subNodeAttributes = $parameter->attributes;
                            $parameterName = $subNodeAttributes->getNamedItem('name')->nodeValue;
                            $widgetArray['parameters'][$parameterName] = $this->_convertParameter($parameter);
                        }
                        break;
                    case 'containers':
                        if (!isset($widgetArray['supported_containers'])) {
                            $widgetArray['supported_containers'] = [];
                        }
                        foreach ($widgetSubNode->childNodes as $container) {
                            if ($container->nodeName === '#text' || $container->nodeName === '#comment') {
                                continue;
                            }
                            $widgetArray['supported_containers'] = array_merge(
                                $widgetArray['supported_containers'],
                                $this->_convertContainer($container)
                            );
                        }
                        break;
                    case 'extra':
                        $widgetArray['extra'] = $this->_convertData($widgetSubNode);
                        break;
                    case "#text":
                        break;
                    case '#comment':
                        break;
                    default:
                        throw new \LogicException(
                            sprintf(
                                "Unsupported child xml node '%s' found in the 'widget' node",
                                $widgetSubNode->nodeName
                            )
                        );
                }
            }
            $widgets[$widgetId->nodeValue] = $widgetArray;
        };

        return $widgets;
    }


    protected function _convertParameter($source)
    {
        $parameter = [];
        $sourceAttributes = $source->attributes;
        $xsiType = $sourceAttributes->getNamedItem('type')->nodeValue;
        if ($xsiType == 'block') {
            $parameter['type'] = 'label';
            $parameter['@'] = [];
            $parameter['@']['type'] = 'complex';
            foreach ($source->childNodes as $blockSubNode) {
                if ($blockSubNode->nodeName == 'block') {
                    $parameter['helper_block'] = $this->_convertBlock($blockSubNode);
                    break;
                }
            }
        } elseif ($xsiType == 'select' || $xsiType == 'multiselect') {
            $sourceModel = $sourceAttributes->getNamedItem('source_model');
            if ($sourceModel !== null) {
                $parameter['source_model'] = $sourceModel->nodeValue;
            }
            $parameter['type'] = $xsiType;

            /** @var $paramSubNode \DOMNode */
            foreach ($source->childNodes as $paramSubNode) {
                if ($paramSubNode->nodeName == 'options') {
                    /** @var $option \DOMNode */
                    foreach ($paramSubNode->childNodes as $option) {
                        if ($option->nodeName === '#text') {
                            continue;
                        }
                        $optionAttributes = $option->attributes;
                        $optionName = $optionAttributes->getNamedItem('name')->nodeValue;
                        $selected = $optionAttributes->getNamedItem('selected');
                        if ($selected !== null) {
                            $parameter['value'] = $optionAttributes->getNamedItem('value')->nodeValue;
                        }
                        if (!isset($parameter['values'])) {
                            $parameter['values'] = [];
                        }
                        $parameter['values'][$optionName] = $this->_convertOption($option);
                    }
                }
            }
        } elseif ($xsiType == 'text') {
            $parameter['type'] = $xsiType;
            foreach ($source->childNodes as $textSubNode) {
                if ($textSubNode->nodeName == 'value') {
                    $parameter['value'] = $textSubNode->nodeValue;
                }
            }
        } elseif ($xsiType == 'conditions') {
            $parameter['type'] = $sourceAttributes->getNamedItem('class')->nodeValue;
        } else {
            $parameter['type'] = $xsiType;
        }
        $visible = $sourceAttributes->getNamedItem('visible');
        if ($visible) {
            $parameter['visible'] = $visible->nodeValue == 'true' ? '1' : '0';
        } else {
            $parameter['visible'] = true;
        }
        $required = $sourceAttributes->getNamedItem('required');
        if ($required) {
            $parameter['required'] = $required->nodeValue == 'false' ? '0' : '1';
        }
        $sortOrder = $sourceAttributes->getNamedItem('sort_order');
        if ($sortOrder) {
            $parameter['sort_order'] = $sortOrder->nodeValue;
        }
        foreach ($source->childNodes as $paramSubNode) {
            switch ($paramSubNode->nodeName) {
                case 'label':
                    $parameter['label'] = $paramSubNode->nodeValue;
                    break;
                case 'description':
                    $parameter['description'] = $paramSubNode->nodeValue;
                    break;
                case 'depends':
                    $parameter['depends'] = $this->_convertDepends($paramSubNode);
                    break;
                case 'extra':
                    $parameter['extra'] = $this->_convertData($paramSubNode);
                    break;
            }
        }
        return $parameter;
    }
}
