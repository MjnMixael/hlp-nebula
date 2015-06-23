<?php

namespace Ngld\AsseticBundle\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Exception\FilterException;
use Assetic\Filter\BaseNodeFilter;

/**
 * Lessc filter.
 *
 * @link http://lesscss.org/#using-less
 * @author ngld <ngld@tproxy.de>
 */
class LesscFilter extends BaseNodeFilter
{
    private $lesscBin;
    private $compress;
    private $ieCompat;
    private $sourceMap;

    public function __construct($lesscBin = '/usr/bin/lessc')
    {
        $this->lesscBin = $lesscBin;
    }

    public function setCompress($compress)
    {
        $this->compress = $compress;
    }

    public function setIeCompat($ieCompat)
    {
        $this->ieCompat = $ieCompat;
    }

    public function setSourceMap($sourceMap)
    {
        $this->sourceMap = $sourceMap;
    }

    public function filterLoad(AssetInterface $asset)
    {
    }

    public function filterDump(AssetInterface $asset)
    {
        $pb = $this->createProcessBuilder(array($this->lesscBin));

        if ($this->compress) {
            $pb->add('--compress');
        }

        if (!$this->ieCompat) {
            $pb->add('--no-ie-compat');
        }

        if ($this->sourceMap) {
            // TODO: Figure out how to place the generated file.
            //$pb->add('--source-map');
        }

        if (($root = $asset->getSourceRoot()) && ($path = $asset->getSourcePath())) {
            $pb->add('--include-path=' . dirname($root . '/' . $path));
        }

        // input and output files
        $input = tempnam(sys_get_temp_dir(), 'input');
        $output = tempnam(sys_get_temp_dir(), 'output');

        file_put_contents($input, $asset->getContent());
        $pb->add($input)->add($output);

        $proc = $pb->getProcess();
        $code = $proc->run();
        unlink($input);

        if (0 !== $code) {
            if (file_exists($output)) {
                unlink($output);
            }

            if (127 === $code) {
                throw new \RuntimeException('Path to node executable could not be resolved.');
            }

            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }

        if (!file_exists($output)) {
            throw new \RuntimeException('Error creating output file.');
        }

        $asset->setContent(file_get_contents($output));

        unlink($output);
    }

    /**
     * @todo support for import-once
     * @todo support for import (less) "lib.css"
     */
    public function getChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        $loadPaths = array(); //$this->loadPaths;
        if (null !== $loadPath) {
            $loadPaths[] = $loadPath;
        }

        if (empty($loadPaths)) {
            return array();
        }

        $children = array();
        foreach (LessUtils::extractImports($content) as $reference) {
            if ('.css' === substr($reference, -4)) {
                // skip normal css imports
                // todo: skip imports with media queries
                continue;
            }

            if ('.less' !== substr($reference, -5)) {
                $reference .= '.less';
            }

            foreach ($loadPaths as $loadPath) {
                if (file_exists($file = $loadPath.'/'.$reference)) {
                    $coll = $factory->createAsset($file, array(), array('root' => $loadPath));
                    foreach ($coll as $leaf) {
                        $leaf->ensureFilter($this);
                        $children[] = $leaf;
                        goto next_reference;
                    }
                }
            }

            next_reference:
        }

        return $children;
    }
}
