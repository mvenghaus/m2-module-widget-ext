<?php

declare(strict_types=1);

namespace Inkl\WidgetExtXsd\Rewrite\Magento\Widget\Model\Config;

use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;

class SchemaLocator implements SchemaLocatorInterface
{
    private string $schema;
    private string $perFileSchema;

    public function __construct(Reader $moduleReader)
    {
        $etcDir = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Inkl_WidgetExtXsd');
        $this->schema = $etcDir . '/widget.xsd';
        $this->perFileSchema = $etcDir . '/widget_file.xsd';
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function getPerFileSchema()
    {
        return $this->perFileSchema;
    }
}
